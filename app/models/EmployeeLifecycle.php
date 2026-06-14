<?php

declare(strict_types=1);

class EmployeeLifecycle extends Model
{
    protected string $table = 'employee_lifecycle_events';
    protected bool $tenantScoped = true;

    public const EVENT_TYPES = [
        'Onboarded',
        'Probation Started',
        'Probation Confirmed',
        'Promoted',
        'Transferred',
        'Suspended',
        'Reinstated',
        'Terminated',
        'Rehired',
        'Salary Changed',
        'Other',
    ];

    public function forEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND ele.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT ele.*, fd.name AS from_department, td.name AS to_department, u.full_name AS created_by_name
             FROM employee_lifecycle_events ele
             LEFT JOIN departments fd ON fd.id = ele.from_department_id
             LEFT JOIN departments td ON td.id = ele.to_department_id
             LEFT JOIN users u ON u.id = ele.created_by
             WHERE ele.employee_id = :employee_id{$and}
             ORDER BY ele.effective_date DESC, ele.id DESC"
        );
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function latestForEmployee(int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND ele.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT ele.*, fd.name AS from_department, td.name AS to_department, u.full_name AS created_by_name
             FROM employee_lifecycle_events ele
             LEFT JOIN departments fd ON fd.id = ele.from_department_id
             LEFT JOIN departments td ON td.id = ele.to_department_id
             LEFT JOIN users u ON u.id = ele.created_by
             WHERE ele.employee_id = :employee_id{$and}
             ORDER BY ele.effective_date DESC, ele.id DESC
             LIMIT 1"
        );
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function reminders(int $days = 30): array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            "SELECT 'Probation Review' AS reminder_type, e.id AS employee_id, e.full_name, e.employee_number,
                    e.probation_end_date AS due_date, e.designation
             FROM employees e
             WHERE e.company_id = :cid
               AND e.lifecycle_status = 'Probation'
               AND e.probation_end_date IS NOT NULL
               AND e.probation_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
             UNION ALL
             SELECT 'Contract Expiry' AS reminder_type, e.id AS employee_id, e.full_name, e.employee_number,
                    ec.end_date AS due_date, e.designation
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             WHERE e.company_id = :cid2
               AND ec.status = 'Active'
               AND ec.approval_status = 'Approved'
               AND ec.end_date IS NOT NULL
               AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days2 DAY)
             ORDER BY due_date ASC"
        );
        $stmt->execute(['cid' => $cid, 'days' => $days, 'cid2' => $cid, 'days2' => $days]);
        return $stmt->fetchAll();
    }

    public function record(int $employeeId, array $data): void
    {
        $employee = (new Employee())->findDetailed($employeeId);
        if (!$employee) {
            throw new RuntimeException('Employee not found.');
        }

        $companyId = (int) ($employee['company_id'] ?? Tenant::id());
        if ($companyId <= 0) {
            throw new RuntimeException('Employee company context could not be resolved.');
        }

        $eventType = (string) ($data['event_type'] ?? 'Other');
        if (!in_array($eventType, self::EVENT_TYPES, true)) {
            $eventType = 'Other';
        }

        $toDepartmentId = (int) ($data['to_department_id'] ?? 0);
        if ($toDepartmentId > 0 && !$this->departmentBelongsToCompany($toDepartmentId, $companyId)) {
            throw new RuntimeException('Selected department does not belong to this company.');
        }

        $toDesignation = trim((string) ($data['to_designation'] ?? ''));
        $toStatus = $this->statusForEvent($eventType, (string) ($employee['lifecycle_status'] ?? $employee['contract_status'] ?? 'Active'));
        $contractStatus = $this->contractStatusForLifecycle($toStatus, (string) ($employee['contract_status'] ?? 'Active'));

        $this->db->beginTransaction();
        try {
            $this->insert([
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'event_type' => $eventType,
                'effective_date' => $data['effective_date'] ?? date('Y-m-d'),
                'from_department_id' => $employee['department_id'] ?? null,
                'to_department_id' => $toDepartmentId > 0 ? $toDepartmentId : null,
                'from_designation' => $employee['designation'] ?? null,
                'to_designation' => $toDesignation !== '' ? $toDesignation : null,
                'from_status' => $employee['lifecycle_status'] ?? $employee['contract_status'] ?? null,
                'to_status' => $toStatus,
                'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $updates = [
                'lifecycle_status' => $toStatus,
                'contract_status' => $contractStatus,
            ];
            if ($toDepartmentId > 0) {
                $updates['department_id'] = $toDepartmentId;
            }
            if ($toDesignation !== '') {
                $updates['designation'] = $toDesignation;
            }
            if ($eventType === 'Probation Started' && !empty($data['probation_end_date'])) {
                $updates['probation_end_date'] = $data['probation_end_date'];
            }
            if ($eventType === 'Terminated') {
                $updates['termination_date'] = $data['effective_date'] ?? date('Y-m-d');
                $updates['portal_active'] = 0;
            }
            if ($eventType === 'Rehired') {
                $updates['termination_date'] = null;
                $updates['archived_at'] = null;
                $updates['archived_by'] = null;
                $updates['portal_active'] = 1;
            }

            (new Employee())->update($employeeId, $updates);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function statusForEvent(string $eventType, string $fallback): string
    {
        return match ($eventType) {
            'Onboarded', 'Probation Confirmed', 'Promoted', 'Transferred', 'Reinstated', 'Rehired', 'Salary Changed' => 'Active',
            'Probation Started' => 'Probation',
            'Suspended' => 'Suspended',
            'Terminated' => 'Terminated',
            default => $fallback !== '' ? $fallback : 'Active',
        };
    }

    private function contractStatusForLifecycle(string $lifecycleStatus, string $fallback): string
    {
        return match ($lifecycleStatus) {
            'Suspended' => 'Suspended',
            'Terminated' => 'Ended',
            default => 'Active',
        };
    }

    private function departmentBelongsToCompany(int $departmentId, int $companyId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM departments WHERE id = :id AND company_id = :company_id');
        $stmt->execute(['id' => $departmentId, 'company_id' => $companyId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

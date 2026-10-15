<?php

declare(strict_types=1);

class EmployeeTerminationRequest extends Model
{
    protected string $table = 'employee_termination_requests';
    protected bool $tenantScoped = true;

    public const FINAL_PAY_MODES = [
        'Use Payroll Run Rule',
        'Full Month',
        'Calendar Days',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS employee_termination_requests (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                effective_date DATE NOT NULL,
                reason TEXT NOT NULL,
                final_pay_mode VARCHAR(30) NOT NULL DEFAULT 'Use Payroll Run Rule',
                status VARCHAR(40) NOT NULL DEFAULT 'Pending Approval',
                current_step INT NOT NULL DEFAULT 1,
                requested_by BIGINT UNSIGNED NULL,
                reviewed_by BIGINT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                approved_by BIGINT UNSIGNED NULL,
                approved_at DATETIME NULL,
                rejected_by BIGINT UNSIGNED NULL,
                rejected_at DATETIME NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_termination_company_status (company_id, status, current_step),
                KEY idx_termination_employee (employee_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach ([
            'termination_proration_mode' => 'VARCHAR(30) NULL',
            'termination_reason' => 'TEXT NULL',
            'termination_request_id' => 'BIGINT UNSIGNED NULL',
        ] as $column => $definition) {
            if (!$this->columnExists('employees', $column)) {
                $this->db->exec("ALTER TABLE employees ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function createForEmployee(int $employeeId, array $data): int
    {
        $employee = (new Employee())->findDetailed($employeeId);
        if (!$employee) {
            throw new RuntimeException('Employee not found.');
        }
        if (!empty($employee['termination_date']) && (string) $employee['termination_date'] <= date('Y-m-d')) {
            throw new RuntimeException('This employee is already terminated.');
        }
        if ($this->pendingForEmployee($employeeId)) {
            throw new RuntimeException('A termination request is already awaiting approval for this employee.');
        }

        $effectiveDate = $this->validDate((string) ($data['effective_date'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));
        $mode = (string) ($data['final_pay_mode'] ?? 'Use Payroll Run Rule');
        if ($effectiveDate === null) {
            throw new RuntimeException('A valid termination effective date is required.');
        }
        if ($reason === '') {
            throw new RuntimeException('A termination reason is required for the audit record.');
        }
        if (!empty($employee['hired_at']) && $effectiveDate < (string) $employee['hired_at']) {
            throw new RuntimeException('The termination date cannot be earlier than the employee hire date.');
        }
        if (!in_array($mode, self::FINAL_PAY_MODES, true)) {
            throw new RuntimeException('The selected final-pay method is invalid.');
        }

        return $this->insert([
            'employee_id' => $employeeId,
            'effective_date' => $effectiveDate,
            'reason' => $reason,
            'final_pay_mode' => $mode,
            'status' => 'Pending Approval',
            'current_step' => 1,
            'requested_by' => (int) ($data['requested_by'] ?? 0) ?: null,
        ]);
    }

    public function pendingForEmployee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM employee_termination_requests
             WHERE company_id = :cid AND employee_id = :employee_id AND status = 'Pending Approval'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['cid' => Tenant::id(), 'employee_id' => $employeeId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT etr.*, e.full_name AS employee_name, e.employee_number, e.lifecycle_status,
                    e.contract_status, e.portal_active, e.hired_at,
                    requester.full_name AS requested_by_name,
                    reviewer.full_name AS reviewed_by_name,
                    approver.full_name AS approved_by_name
             FROM employee_termination_requests etr
             JOIN employees e ON e.id = etr.employee_id AND e.company_id = etr.company_id
             LEFT JOIN users requester ON requester.id = etr.requested_by
             LEFT JOIN users reviewer ON reviewer.id = etr.reviewed_by
             LEFT JOIN users approver ON approver.id = etr.approved_by
             WHERE etr.id = :id AND etr.company_id = :cid
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function pendingForCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            "SELECT etr.*, e.full_name, e.employee_number
             FROM employee_termination_requests etr
             JOIN employees e ON e.id = etr.employee_id AND e.company_id = etr.company_id
             WHERE etr.company_id = :cid AND etr.status = 'Pending Approval'
             ORDER BY etr.created_at ASC, etr.id ASC"
        );
        $stmt->execute(['cid' => $companyId]);

        return $stmt->fetchAll();
    }

    public function payrollImpact(int $employeeId, string $effectiveDate, string $mode): array
    {
        $date = $this->validDate($effectiveDate);
        if ($date === null) {
            throw new RuntimeException('A valid effective date is required.');
        }
        if (!in_array($mode, self::FINAL_PAY_MODES, true)) {
            $mode = 'Use Payroll Run Rule';
        }

        $periodStart = date('Y-m-01', strtotime($date));
        $periodEnd = date('Y-m-t', strtotime($date));
        $periodDays = (int) date('t', strtotime($date));
        $employee = (new Employee())->findDetailed($employeeId);
        if (!$employee) {
            throw new RuntimeException('Employee not found.');
        }
        $employmentStart = !empty($employee['hired_at']) && (string) $employee['hired_at'] > $periodStart
            ? (string) $employee['hired_at']
            : $periodStart;
        $eligibleDays = $date >= $employmentStart
            ? (int) floor((strtotime($date) - strtotime($employmentStart)) / 86400) + 1
            : 0;
        $resolvedMode = $mode === 'Use Payroll Run Rule' ? 'Configured on the payroll run' : $mode;
        $factor = $mode === 'Calendar Days' ? round($eligibleDays / max(1, $periodDays), 8) : 1.0;

        $salary = (new EmployeeSalary())->activeWithStructureForDate($employeeId, $date);
        $basic = (float) ($salary['basic_pay'] ?? 0);
        $allowances = (float) ($salary['housing_allowance'] ?? 0)
            + (float) ($salary['transport_allowance'] ?? 0)
            + (float) ($salary['other_allowances'] ?? 0);
        $estimatedGross = $mode === 'Calendar Days'
            ? round(($basic + $allowances) * $factor, 2)
            : round($basic + $allowances, 2);

        return [
            'period' => date('F Y', strtotime($periodStart)),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'eligible_days' => $eligibleDays,
            'period_days' => $periodDays,
            'factor' => $factor,
            'factor_percent' => round($factor * 100, 2),
            'mode' => $mode,
            'resolved_mode' => $resolvedMode,
            'estimated_gross' => $estimatedGross,
            'currency_code' => (string) ($salary['currency_code'] ?? app_currency_code()),
            'next_period' => date('F Y', strtotime($periodEnd . ' +1 day')),
        ];
    }

    public function advanceApproval(int $id, int $actorUserId): string
    {
        $request = $this->findDetailed($id);
        if (!$request || (string) $request['status'] !== 'Pending Approval') {
            throw new RuntimeException('Termination request not found or already actioned.');
        }

        $step = max(1, (int) ($request['current_step'] ?? 1));
        $stepCount = (new WorkflowDefinition())->stepCount('employee_termination');
        $stepCount = $stepCount > 0 ? $stepCount : 2;

        if ($step < $stepCount) {
            $advance = $this->db->prepare(
                "UPDATE employee_termination_requests
                 SET current_step = :next_step, reviewed_by = :reviewed_by, reviewed_at = NOW()
                 WHERE id = :id AND company_id = :cid
                   AND status = 'Pending Approval' AND current_step = :current_step"
            );
            $advance->execute([
                'next_step' => $step + 1,
                'reviewed_by' => $actorUserId ?: null,
                'id' => $id,
                'cid' => Tenant::id(),
                'current_step' => $step,
            ]);
            if ($advance->rowCount() !== 1) {
                throw new RuntimeException('This termination request was already actioned by another approver.');
            }
            return 'Pending Approval';
        }

        $claim = $this->db->prepare(
            "UPDATE employee_termination_requests
             SET status = 'Applying'
             WHERE id = :id AND company_id = :cid
               AND status = 'Pending Approval' AND current_step = :current_step"
        );
        $claim->execute(['id' => $id, 'cid' => Tenant::id(), 'current_step' => $step]);
        if ($claim->rowCount() !== 1) {
            throw new RuntimeException('This termination request was already actioned by another approver.');
        }

        try {
            $this->applyApprovedTermination($request, $actorUserId);
            $this->update($id, [
                'status' => 'Approved',
                'approved_by' => $actorUserId ?: null,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            $this->update($id, ['status' => 'Pending Approval']);
            throw $e;
        }

        return 'Approved';
    }

    public function rejectRequest(int $id, int $actorUserId, string $reason): void
    {
        $request = $this->findDetailed($id);
        if (!$request || (string) $request['status'] !== 'Pending Approval') {
            throw new RuntimeException('Termination request not found or already actioned.');
        }

        $reject = $this->db->prepare(
            "UPDATE employee_termination_requests
             SET status = 'Rejected', rejected_by = :rejected_by, rejected_at = NOW(),
                 rejection_reason = :rejection_reason
             WHERE id = :id AND company_id = :cid AND status = 'Pending Approval'"
        );
        $reject->execute([
            'rejected_by' => $actorUserId ?: null,
            'rejection_reason' => trim($reason) ?: null,
            'id' => $id,
            'cid' => Tenant::id(),
        ]);
        if ($reject->rowCount() !== 1) {
            throw new RuntimeException('This termination request was already actioned by another approver.');
        }
    }

    private function applyApprovedTermination(array $request, int $actorUserId): void
    {
        $employeeId = (int) $request['employee_id'];
        $employeeModel = new Employee();
        $checklist = new EmployeeLifecycleChecklist();

        (new EmployeeLifecycle())->record($employeeId, [
            'event_type' => 'Terminated',
            'effective_date' => (string) $request['effective_date'],
            'notes' => (string) $request['reason'],
            'created_by' => $actorUserId ?: null,
        ]);

        $employeeModel->update($employeeId, [
            'termination_proration_mode' => (string) $request['final_pay_mode'],
            'termination_reason' => (string) $request['reason'],
            'termination_request_id' => (int) $request['id'],
        ]);

        $contract = $this->db->prepare(
            "UPDATE employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             SET ec.status = 'Terminated'
             WHERE ec.employee_id = :employee_id AND e.company_id = :cid AND ec.status = 'Active'"
        );
        $contract->execute(['employee_id' => $employeeId, 'cid' => Tenant::id()]);

        $checklist->ensureDefaults($employeeId, 'Exit');
        $checklist->completeByLabel($employeeId, 'Exit', 'Termination/exit date confirmed', $actorUserId);
        $checklist->completeByLabel($employeeId, 'Exit', 'Portal access disabled', $actorUserId);
    }

    private function validDate(string $value): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));
        return $date && $date->format('Y-m-d') === trim($value) ? $date->format('Y-m-d') : null;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

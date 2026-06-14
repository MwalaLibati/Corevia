<?php

declare(strict_types=1);

/**
 * Employee contract model — tracks contract lifecycle per employee.
 */

class EmployeeContract extends Model
{
    protected string $table = 'employee_contracts';

    public function generateContractNumber(): string
    {
        $sql = "SELECT contract_number
                FROM employee_contracts
                WHERE contract_number REGEXP '^CNT[0-9]{6}$'
                ORDER BY contract_number DESC
                LIMIT 1";

        $last = (string) $this->db->query($sql)->fetchColumn();

        if ($last === '') {
            return 'CNT000001';
        }

        $seq = (int) substr($last, 3) + 1;

        return 'CNT' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    public function listAll(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? 'WHERE e.company_id = :cid' : '';
        $sql = 'SELECT ec.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                       d.name AS department_name, ct.name AS template_name
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN contract_templates ct ON ct.id = ec.template_id
                ' . $and . '
                ORDER BY
                    CASE ec.status WHEN \'Active\' THEN 0 ELSE 1 END,
                    ec.end_date ASC,
                    ec.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT ec.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                       d.name AS department_name, ct.name AS template_name
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN contract_templates ct ON ct.id = ec.template_id
                WHERE (e.full_name LIKE :keyword
                   OR e.employee_number LIKE :keyword
                   OR ec.contract_number LIKE :keyword)' . $and . '
                ORDER BY ec.end_date ASC, ec.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listForEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT ec.* FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.employee_id = :employee_id' . $and . '
                ORDER BY start_date DESC, id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function activeForEmployee(int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = "SELECT ec.* FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.employee_id = :employee_id AND ec.status = 'Active' AND ec.approval_status = 'Approved'$and
                ORDER BY start_date DESC, id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT ec.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                       d.name AS department_name, ct.name AS template_name
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN contract_templates ct ON ct.id = ec.template_id
                WHERE ec.id = :id' . $and . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function expiringWithinDays(int $days): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = "SELECT ec.*, e.full_name AS employee_name, e.employee_number,
                       d.name AS department_name
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE ec.status = 'Active'
                  AND ec.end_date IS NOT NULL
                  AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)$and
                ORDER BY ec.end_date ASC";

        $stmt = $this->db->prepare($sql);
        $params = ['days' => $days];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countExpiring(int $days): int
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = "SELECT COUNT(*) FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.status = 'Active'
                  AND ec.end_date IS NOT NULL
                  AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)$and";

        $stmt = $this->db->prepare($sql);
        $params = ['days' => $days];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             WHERE ec.status = :status$and"
        );
        $params = ['status' => $status];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function recentlyExpired(int $days = 7): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = "SELECT ec.*, e.full_name AS employee_name, e.employee_number
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.status = 'Expired'
                  AND ec.end_date IS NOT NULL
                  AND ec.end_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)$and
                ORDER BY ec.end_date DESC";

        $stmt = $this->db->prepare($sql);
        $params = ['days' => $days];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function autoExpire(): void
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $this->db->prepare(
                "UPDATE employee_contracts ec
                 JOIN employees e ON e.id = ec.employee_id
                 SET ec.status = 'Expired'
                 WHERE ec.status = 'Active'
                   AND ec.end_date IS NOT NULL
                   AND ec.end_date < CURDATE()
                   AND e.company_id = :cid"
            )->execute(['cid' => $cid]);
        } else {
            $this->db->exec(
                "UPDATE employee_contracts
                 SET status = 'Expired'
                 WHERE status = 'Active'
                   AND end_date IS NOT NULL
                   AND end_date < CURDATE()"
            );
        }

        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "UPDATE employees e
             SET e.contract_status = 'Ended'
             WHERE e.contract_status = 'Active'$and
               AND NOT EXISTS (
                   SELECT 1 FROM employee_contracts ec
                   WHERE ec.employee_id = e.id AND ec.status = 'Active'
               )
               AND EXISTS (
                   SELECT 1 FROM employee_contracts ec
                   WHERE ec.employee_id = e.id
               )"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
    }

    public function createContract(int $employeeId, string $contractType, string $startDate, ?string $endDate, ?string $notes, ?int $createdBy, ?int $templateId = null): int
    {
        $this->db->beginTransaction();

        try {
            $contractNumber = $this->generateContractNumber();

            $this->insert([
                'employee_id'     => $employeeId,
                'contract_number' => $contractNumber,
                'contract_type'   => $contractType,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'status'          => 'Active',
                'approval_status' => 'Pending HR Review',
                'notes'           => $notes,
                'created_by'      => $createdBy,
                'template_id'     => $templateId,
            ]);

            $newId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return $newId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function terminateContract(int $contractId): void
    {
        $contract = $this->find($contractId);

        if (!$contract) {
            throw new RuntimeException('Contract not found.');
        }

        $this->db->beginTransaction();

        try {
            $this->update($contractId, ['status' => 'Terminated']);

            $hasOtherActive = $this->activeForEmployee((int) $contract['employee_id']);

            if (!$hasOtherActive) {
                $this->db->prepare(
                    "UPDATE employees SET contract_status = 'Ended' WHERE id = :id"
                )->execute(['id' => $contract['employee_id']]);
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function advanceApproval(int $contractId, string $action, ?string $reason = null): string
    {
        $contract = $this->find($contractId);
        if (!$contract) {
            throw new RuntimeException('Contract not found.');
        }

        $current = (string) ($contract['approval_status'] ?? 'Approved');
        $userId = (int) (current_user()['id'] ?? 0) ?: null;
        $workflow = new WorkflowDefinition();

        if ($action === 'reject') {
            if (!in_array($current, ['Pending HR Review', 'Pending Admin Approval'], true)) {
                throw new RuntimeException('This contract cannot be rejected from its current approval state.');
            }
            $this->update($contractId, ['approval_status' => 'Rejected', 'rejection_reason' => $reason]);
            WorkflowEvent::record('contract', 'EmployeeContract', $contractId, $current, 'Rejected', 'contract_reject', $reason);
            return 'Rejected';
        }

        if ($current === 'Pending HR Review') {
            $requiredRole = $workflow->requiredRoleFor('contract', 1, 'HR Officer');
            if (!$this->userMatchesWorkflowRole($requiredRole)) {
                throw new RuntimeException("This workflow step requires {$requiredRole} approval.");
            }
            $this->update($contractId, ['approval_status' => 'Pending Admin Approval']);
            WorkflowEvent::record('contract', 'EmployeeContract', $contractId, $current, 'Pending Admin Approval', 'contract_hr_review', $workflow->actionLabelFor('contract', 1, 'Review Contract'));
            return 'Pending Admin Approval';
        }

        if ($current === 'Pending Admin Approval') {
            $requiredRole = $workflow->requiredRoleFor('contract', 2, 'Super Admin');
            if (!$this->userMatchesWorkflowRole($requiredRole)) {
                throw new RuntimeException("This workflow step requires {$requiredRole} approval.");
            }
            $this->db->beginTransaction();
            try {
                $this->update($contractId, [
                    'approval_status' => 'Approved',
                    'approved_by' => $userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);
                $this->db->prepare("UPDATE employees SET employment_type = :type, contract_status = 'Active' WHERE id = :id")
                    ->execute(['type' => $contract['contract_type'], 'id' => $contract['employee_id']]);
                $this->db->commit();
            } catch (Throwable $e) {
                if ($this->db->inTransaction()) { $this->db->rollBack(); }
                throw $e;
            }
            WorkflowEvent::record('contract', 'EmployeeContract', $contractId, $current, 'Approved', 'contract_admin_approve', $workflow->actionLabelFor('contract', 2, 'Approve Contract'));
            return 'Approved';
        }

        throw new RuntimeException('This contract is not awaiting approval.');
    }

    public function renewContract(int $originalContractId, string $contractType, string $startDate, ?string $endDate, ?string $notes, ?int $createdBy, ?int $templateId = null): int
    {
        $original = $this->find($originalContractId);

        if (!$original) {
            throw new RuntimeException('Original contract not found.');
        }

        $this->db->beginTransaction();

        try {
            $this->update($originalContractId, ['status' => 'Renewed']);

            $contractNumber = $this->generateContractNumber();

            $this->insert([
                'employee_id'     => (int) $original['employee_id'],
                'contract_number' => $contractNumber,
                'contract_type'   => $contractType,
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'status'          => 'Active',
                'approval_status' => 'Pending HR Review',
                'notes'           => $notes,
                'created_by'      => $createdBy,
                'template_id'     => $templateId,
            ]);

            $newId = (int) $this->db->lastInsertId();

            $this->db->prepare(
                "UPDATE employees SET employment_type = :type, contract_status = 'Active' WHERE id = :id"
            )->execute(['type' => $contractType, 'id' => $original['employee_id']]);

            $this->db->commit();

            return $newId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function employees(): array
    {
        return $this->db->query(
            "SELECT id, full_name, employee_number FROM employees ORDER BY full_name ASC"
        )->fetchAll();
    }

    private function userMatchesWorkflowRole(string $requiredRole): bool
    {
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        return $role === 'Super Admin'
            || $accessLevel === 'Super Admin'
            || $role === $requiredRole
            || $accessLevel === $requiredRole;
    }
}

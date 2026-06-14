<?php

declare(strict_types=1);

class ContractRenewalRequest extends Model
{
    protected string $table = 'contract_renewal_requests';
    private bool $schemaReady = false;

    public function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS contract_renewal_requests (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                contract_id BIGINT UNSIGNED NOT NULL,
                requested_end_date DATE NULL,
                reason TEXT NULL,
                status ENUM('Pending','Renewed','Dismissed') NOT NULL DEFAULT 'Pending',
                reviewed_by BIGINT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                review_notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_contract_renewal_company_status (company_id, status),
                INDEX idx_contract_renewal_employee_status (employee_id, status),
                INDEX idx_contract_renewal_contract_status (contract_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->schemaReady = true;
    }

    public function pendingForEmployeeContract(int $employeeId, int $contractId): ?array
    {
        $this->ensureSchema();

        $stmt = $this->db->prepare(
            "SELECT * FROM contract_renewal_requests
             WHERE employee_id = :employee_id
               AND contract_id = :contract_id
               AND status = 'Pending'
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute(['employee_id' => $employeeId, 'contract_id' => $contractId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function pendingForEmployee(int $employeeId): array
    {
        $this->ensureSchema();

        $stmt = $this->db->prepare(
            "SELECT * FROM contract_renewal_requests
             WHERE employee_id = :employee_id
               AND status = 'Pending'
             ORDER BY created_at DESC, id DESC"
        );
        $stmt->execute(['employee_id' => $employeeId]);

        return $stmt->fetchAll();
    }

    public function pendingForCompany(int $companyId = 0): array
    {
        $this->ensureSchema();

        $where = "WHERE crr.status = 'Pending'";
        $params = [];
        if ($companyId > 0) {
            $where .= ' AND e.company_id = :company_id';
            $params['company_id'] = $companyId;
        }

        $stmt = $this->db->prepare(
            "SELECT crr.*, ec.contract_number, ec.contract_type, ec.start_date, ec.end_date,
                    e.full_name AS employee_name, e.employee_number
             FROM contract_renewal_requests crr
             JOIN employee_contracts ec ON ec.id = crr.contract_id
             JOIN employees e ON e.id = crr.employee_id
             $where
             ORDER BY crr.created_at ASC, crr.id ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function submit(int $employeeId, int $contractId, ?string $requestedEndDate, ?string $reason): int
    {
        $this->ensureSchema();

        $existing = $this->pendingForEmployeeContract($employeeId, $contractId);
        if ($existing) {
            throw new RuntimeException('You already have a pending renewal request for this contract.');
        }

        $companyId = $this->companyIdForEmployee($employeeId);

        return $this->insert([
            'company_id' => $companyId > 0 ? $companyId : null,
            'employee_id' => $employeeId,
            'contract_id' => $contractId,
            'requested_end_date' => $requestedEndDate,
            'reason' => $reason,
            'status' => 'Pending',
        ]);
    }

    public function markRenewedForContract(int $contractId, int $reviewedBy, ?string $notes = null): void
    {
        $this->ensureSchema();

        $stmt = $this->db->prepare(
            "UPDATE contract_renewal_requests
             SET status = 'Renewed',
                 reviewed_by = :reviewed_by,
                 reviewed_at = NOW(),
                 review_notes = :notes
             WHERE contract_id = :contract_id
               AND status = 'Pending'"
        );
        $stmt->execute([
            'reviewed_by' => $reviewedBy > 0 ? $reviewedBy : null,
            'notes' => $notes,
            'contract_id' => $contractId,
        ]);
    }

    private function companyIdForEmployee(int $employeeId): int
    {
        $stmt = $this->db->prepare('SELECT company_id FROM employees WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $employeeId]);

        return (int) $stmt->fetchColumn();
    }
}

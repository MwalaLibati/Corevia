<?php

declare(strict_types=1);

class EmployeeProfileChangeRequest extends Model
{
    protected string $table = 'employee_profile_change_requests';
    protected bool $tenantScoped = true;

    public function pendingForEmployee(int $employeeId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM employee_profile_change_requests
             WHERE company_id = :cid AND employee_id = :eid AND status = 'Pending'
             ORDER BY id DESC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function forEmployee(int $employeeId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT epcr.*, u.full_name AS reviewed_by_name
             FROM employee_profile_change_requests epcr
             LEFT JOIN users u ON u.id = epcr.reviewed_by
             WHERE epcr.company_id = :cid AND epcr.employee_id = :eid
             ORDER BY epcr.id DESC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function listPending(int $employeeId): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT epcr.*, e.full_name, e.employee_number
             FROM employee_profile_change_requests epcr
             JOIN employees e ON e.id = epcr.employee_id
             WHERE epcr.company_id = :cid AND epcr.employee_id = :eid AND epcr.status = 'Pending'
             ORDER BY epcr.id DESC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function createForChanges(int $employeeId, array $changes): ?int
    {
        if ($changes === []) {
            return null;
        }

        if (!$this->tableExists()) {
            throw new RuntimeException('Profile change approvals are not installed yet. Please run the employee portal completion migration.');
        }

        return $this->insert([
            'company_id' => Tenant::id(),
            'employee_id' => $employeeId,
            'requested_changes_json' => json_encode($changes, JSON_THROW_ON_ERROR),
            'status' => 'Pending',
        ]);
    }

    public function decodedChanges(array $request): array
    {
        $json = (string) ($request['requested_changes_json'] ?? '{}');
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function approve(int $id, int $reviewerId): void
    {
        if (!$this->tableExists()) {
            throw new RuntimeException('Profile change approvals are not installed yet. Please run the employee portal completion migration.');
        }

        $request = $this->find($id);
        if (!$request || (string) ($request['status'] ?? '') !== 'Pending') {
            throw new RuntimeException('Profile change request not found or already reviewed.');
        }

        $changes = $this->decodedChanges($request);
        $updates = [];
        foreach ($changes as $field => $change) {
            if (is_array($change) && array_key_exists('new', $change)) {
                $updates[$field] = $change['new'];
            }
        }

        if ($updates === []) {
            throw new RuntimeException('No profile changes to apply.');
        }

        $this->db->beginTransaction();
        try {
            (new Employee())->update((int) $request['employee_id'], $updates);
            $this->update($id, [
                'status' => 'Approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function reject(int $id, int $reviewerId, string $notes = ''): void
    {
        if (!$this->tableExists()) {
            throw new RuntimeException('Profile change approvals are not installed yet. Please run the employee portal completion migration.');
        }

        $request = $this->find($id);
        if (!$request || (string) ($request['status'] ?? '') !== 'Pending') {
            throw new RuntimeException('Profile change request not found or already reviewed.');
        }

        $this->update($id, [
            'status' => 'Rejected',
            'review_notes' => $notes !== '' ? $notes : null,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $this->ensureSchema();
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => $this->table]);
            $exists = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $exists = false;
        }

        return $exists;
    }

    private function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS employee_profile_change_requests (
              id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              company_id BIGINT UNSIGNED NOT NULL,
              employee_id BIGINT UNSIGNED NOT NULL,
              requested_changes_json JSON NOT NULL,
              status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
              review_notes TEXT NULL,
              reviewed_by BIGINT UNSIGNED NULL,
              reviewed_at DATETIME NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_epcr_company_employee_status (company_id, employee_id, status),
              KEY idx_epcr_employee (employee_id),
              KEY idx_epcr_reviewed_by (reviewed_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }
}

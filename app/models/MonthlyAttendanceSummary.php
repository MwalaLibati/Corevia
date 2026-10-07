<?php

declare(strict_types=1);

/**
 * Authoritative monthly hours entered when a company does not use daily clock times.
 */
class MonthlyAttendanceSummary extends Model
{
    protected string $table = 'attendance_monthly_summaries';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        if (!$this->tableExists('attendance_monthly_summaries')) {
            if ($this->db->inTransaction()) {
                throw new RuntimeException('Monthly attendance database setup is pending. Run the attendance migration before payroll.');
            }
            $this->ensureSchema();
        }
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS attendance_monthly_summaries (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                attendance_month DATE NOT NULL,
                total_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                overtime_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                notes TEXT NULL,
                status ENUM('Draft','Approved','Locked') NOT NULL DEFAULT 'Draft',
                created_by BIGINT UNSIGNED NULL,
                approved_by BIGINT UNSIGNED NULL,
                approved_at DATETIME NULL,
                locked_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_att_monthly_employee_period (company_id, employee_id, attendance_month),
                KEY idx_att_monthly_period_status (company_id, attendance_month, status),
                KEY idx_att_monthly_employee (employee_id, attendance_month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function listForMonth(string $month): array
    {
        $monthDate = $this->monthDate($month);
        $stmt = $this->db->prepare(
            "SELECT ams.*, e.employee_number, e.full_name AS employee_name,
                    e.pay_calculation_method,
                    u.full_name AS created_by_name, approver.full_name AS approved_by_name,
                    (SELECT COUNT(*) FROM attendance_records ar
                     WHERE ar.employee_id = ams.employee_id
                       AND ar.attendance_date >= ams.attendance_month
                       AND ar.attendance_date < DATE_ADD(ams.attendance_month, INTERVAL 1 MONTH)) AS daily_record_count
             FROM attendance_monthly_summaries ams
             JOIN employees e ON e.id = ams.employee_id AND e.company_id = ams.company_id
             LEFT JOIN users u ON u.id = ams.created_by
             LEFT JOIN users approver ON approver.id = ams.approved_by
             WHERE ams.company_id = :cid AND ams.attendance_month = :attendance_month
             ORDER BY e.full_name ASC, e.employee_number ASC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'attendance_month' => $monthDate]);
        return $stmt->fetchAll();
    }

    public function forEmployeeMonth(int $employeeId, string $month, bool $approvedOnly = false): ?array
    {
        $sql = "SELECT ams.*, e.employee_number, e.full_name AS employee_name, e.pay_calculation_method
                FROM attendance_monthly_summaries ams
                JOIN employees e ON e.id = ams.employee_id AND e.company_id = ams.company_id
                WHERE ams.company_id = :cid
                  AND ams.employee_id = :employee_id
                  AND ams.attendance_month = :attendance_month";
        if ($approvedOnly) {
            $sql .= " AND ams.status IN ('Approved','Locked')";
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'cid' => Tenant::id(),
            'employee_id' => $employeeId,
            'attendance_month' => $this->monthDate($month),
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public function approvedForMonth(string $month): array
    {
        $stmt = $this->db->prepare(
            "SELECT ams.*, e.employee_number, e.full_name AS employee_name,
                    COALESCE(e.pay_calculation_method, 'Fixed Monthly Salary') AS pay_calculation_method
             FROM attendance_monthly_summaries ams
             JOIN employees e ON e.id = ams.employee_id AND e.company_id = ams.company_id
             WHERE ams.company_id = :cid
               AND ams.attendance_month = :attendance_month
               AND ams.status IN ('Approved','Locked')"
        );
        $stmt->execute(['cid' => Tenant::id(), 'attendance_month' => $this->monthDate($month)]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['employee_id']] = $row;
        }
        return $rows;
    }

    public function saveEntry(array $data): int
    {
        $existing = $this->forEmployeeMonth((int) $data['employee_id'], (string) $data['attendance_month']);
        if ($existing && (string) $existing['status'] === 'Locked') {
            throw new RuntimeException('This monthly attendance entry is locked by payroll and cannot be changed.');
        }

        $monthDate = $this->monthDate((string) $data['attendance_month']);
        $status = in_array((string) ($data['status'] ?? 'Draft'), ['Draft', 'Approved'], true)
            ? (string) $data['status']
            : 'Draft';
        $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $approved = $status === 'Approved';

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE attendance_monthly_summaries
                 SET total_hours = :total_hours, overtime_hours = :overtime_hours, notes = :notes,
                     status = :status, approved_by = :approved_by, approved_at = :approved_at
                 WHERE id = :id AND company_id = :cid"
            );
            $stmt->execute([
                'total_hours' => $data['total_hours'],
                'overtime_hours' => $data['overtime_hours'],
                'notes' => $data['notes'] ?: null,
                'status' => $status,
                'approved_by' => $approved && $userId > 0 ? $userId : null,
                'approved_at' => $approved ? date('Y-m-d H:i:s') : null,
                'id' => (int) $existing['id'],
                'cid' => Tenant::id(),
            ]);
            return (int) $existing['id'];
        }

        return $this->insert([
            'employee_id' => (int) $data['employee_id'],
            'attendance_month' => $monthDate,
            'total_hours' => $data['total_hours'],
            'overtime_hours' => $data['overtime_hours'],
            'notes' => $data['notes'] ?: null,
            'status' => $status,
            'created_by' => $userId > 0 ? $userId : null,
            'approved_by' => $approved && $userId > 0 ? $userId : null,
            'approved_at' => $approved ? date('Y-m-d H:i:s') : null,
        ]);
    }

    public function approve(int $id): bool
    {
        $userId = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $stmt = $this->db->prepare(
            "UPDATE attendance_monthly_summaries
             SET status = 'Approved', approved_by = :approved_by, approved_at = NOW()
             WHERE id = :id AND company_id = :cid AND status = 'Draft'"
        );
        $stmt->execute(['approved_by' => $userId > 0 ? $userId : null, 'id' => $id, 'cid' => Tenant::id()]);
        return $stmt->rowCount() > 0;
    }

    public function reopen(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE attendance_monthly_summaries
             SET status = 'Draft', approved_by = NULL, approved_at = NULL
             WHERE id = :id AND company_id = :cid AND status = 'Approved'"
        );
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        return $stmt->rowCount() > 0;
    }

    public function deleteEditable(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM attendance_monthly_summaries
             WHERE id = :id AND company_id = :cid AND status <> 'Locked'"
        );
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        return $stmt->rowCount() > 0;
    }

    public function lockMonth(string $month): void
    {
        $stmt = $this->db->prepare(
            "UPDATE attendance_monthly_summaries
             SET status = 'Locked', locked_at = NOW()
             WHERE company_id = :cid AND attendance_month = :attendance_month AND status = 'Approved'"
        );
        $stmt->execute(['cid' => Tenant::id(), 'attendance_month' => $this->monthDate($month)]);
    }

    private function monthDate(string $month): string
    {
        $month = substr(trim($month), 0, 7);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            throw new InvalidArgumentException('A valid attendance month is required.');
        }
        return $month . '-01';
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
        );
        $stmt->execute(['table_name' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

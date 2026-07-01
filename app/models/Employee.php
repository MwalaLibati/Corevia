<?php

declare(strict_types=1);

/**
 * Employee model for staff CRUD and filtering.
 */

class Employee extends Model
{
    protected string $table = 'employees';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        (new ClientEntity())->ensureSchema();
        (new Branch())->ensureSchema();
        $this->ensurePortalAccessSchema();
    }

    public function ensurePortalAccessSchema(): void
    {
        $columns = [
            'portal_must_change_password' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'portal_password_set_at' => 'DATETIME NULL',
            'portal_password_expires_at' => 'DATETIME NULL',
            'portal_invite_sent_at' => 'DATETIME NULL',
            'portal_password_reset_at' => 'DATETIME NULL',
            'portal_last_login_at' => 'DATETIME NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists('employees', $column)) {
                $this->db->exec("ALTER TABLE employees ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function generateNextEmployeeNumber(): string
    {
        $cid = Tenant::id();
        $sql = "SELECT employee_number
                FROM employees
                WHERE employee_number REGEXP '^EMP[0-9]{6}$'
                " . ($cid > 0 ? "AND company_id = $cid" : '') . "
                ORDER BY employee_number DESC
                LIMIT 1";

        $lastEmployeeNumber = (string) $this->db->query($sql)->fetchColumn();

        if ($lastEmployeeNumber === '') {
            return 'EMP000001';
        }

        $lastSequence = (int) substr($lastEmployeeNumber, 3);
        $nextSequence = $lastSequence + 1;

        return 'EMP' . str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $tidFilter = $cid > 0 ? 'AND e.company_id = :cid' : '';
        $sql = "SELECT e.*, d.name AS department_name, b.name AS branch_name, c.name AS company_name,
                       ss.name AS active_salary_structure_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN companies c ON c.id = e.company_id
                LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
                LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
                WHERE e.archived_at IS NULL
                  AND (e.full_name LIKE :keyword
                   OR e.employee_number LIKE :keyword
                   OR e.email LIKE :keyword)
                $tidFilter
                ORDER BY e.id DESC";

        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listWithDepartment(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE e.company_id = :cid AND e.archived_at IS NULL' : 'WHERE e.archived_at IS NULL';
        $sql = "SELECT e.*, d.name AS department_name, b.name AS branch_name, c.name AS company_name,
                       ss.name AS active_salary_structure_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN companies c ON c.id = e.company_id
                LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
                LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
                $where
                ORDER BY e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT e.*, d.name AS department_name, d.code AS department_code,
                       b.name AS branch_name, b.code AS branch_code, b.address AS branch_address,
                       b.phone AS branch_phone, b.email AS branch_email,
                       c.name AS company_name, ce.name AS client_entity_name, ce.code AS client_entity_code,
                       g.name AS gender_name
                FROM employees e
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN branches b ON b.id = e.branch_id
                LEFT JOIN companies c ON c.id = e.company_id
                LEFT JOIN client_entities ce ON ce.id = c.client_entity_id
                LEFT JOIN genders g ON g.id = e.gender_id
                WHERE e.id = :id' . $and . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function employeeNumberExists(string $employeeNumber, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM employees WHERE employee_number = :employee_number'
            . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['employee_number' => $employeeNumber];
        if ($cid > 0) { $params['cid'] = $cid; }

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM employees WHERE email = :email'
            . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['email' => $email];
        if ($cid > 0) { $params['cid'] = $cid; }

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function activatePortalAccess(int $employeeId, string $passwordHash, int $expiresHours = 72): void
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(1, $expiresHours) . ' hours'));
        $stmt = $this->db->prepare(
            "UPDATE employees
             SET portal_active = 1,
                 portal_password_hash = :hash,
                 portal_must_change_password = 1,
                 portal_password_set_at = NOW(),
                 portal_password_reset_at = NOW(),
                 portal_password_expires_at = :expires_at,
                 portal_invite_sent_at = NOW()
             WHERE id = :id{$and}"
        );
        $params = ['id' => $employeeId, 'hash' => $passwordHash, 'expires_at' => $expiresAt];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);
    }

    public function deactivatePortalAccess(int $employeeId): void
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "UPDATE employees
             SET portal_active = 0,
                 portal_must_change_password = 0,
                 portal_password_expires_at = NULL
             WHERE id = :id{$and}"
        );
        $params = ['id' => $employeeId];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);
    }

    public function departments(): array
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $stmt = $this->db->prepare('SELECT id, name FROM departments WHERE company_id = :cid ORDER BY name ASC');
            $stmt->execute(['cid' => $cid]);
        } else {
            $stmt = $this->db->query('SELECT id, name FROM departments ORDER BY name ASC');
        }
        return $stmt->fetchAll();
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => $table, 'column' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function branches(): array
    {
        return (new Branch())->activeOptions();
    }

    public function payrollProfileSummary(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND pr.company_id = :run_cid' : '';
        $sql = "SELECT
                    COUNT(pi.id) AS payslip_count,
                    COALESCE(SUM(pi.gross_pay), 0) AS total_gross,
                    COALESCE(SUM(pi.total_deductions), 0) AS total_deductions,
                    COALESCE(SUM(pi.net_pay), 0) AS total_net,
                    COALESCE(SUM(COALESCE(item_pay.paid_amount, 0)), 0) AS paid_so_far
                FROM payroll_items pi
                JOIN employees e ON e.id = pi.employee_id
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                LEFT JOIN (
                    SELECT payroll_item_id, SUM(amount) AS paid_amount
                    FROM payroll_item_payments
                    GROUP BY payroll_item_id
                ) item_pay ON item_pay.payroll_item_id = pi.id
                WHERE pi.employee_id = :employee_id{$and}";

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['run_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        $totalNet = (float) ($row['total_net'] ?? 0);
        $paid = (float) ($row['paid_so_far'] ?? 0);

        return [
            'payslip_count' => (int) ($row['payslip_count'] ?? 0),
            'total_gross' => (float) ($row['total_gross'] ?? 0),
            'total_deductions' => (float) ($row['total_deductions'] ?? 0),
            'total_net' => $totalNet,
            'paid_so_far' => $paid,
            'outstanding' => max(0.0, $totalNet - $paid),
        ];
    }

    public function payrollProfileHistory(int $employeeId, int $limit = 8): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND pr.company_id = :run_cid' : '';
        $sql = "SELECT pi.*, pr.pay_period, pr.run_date, pr.status AS run_status,
                       pr.total_net AS run_total_net,
                       COALESCE(item_pay.paid_amount, 0) AS employee_paid_amount
                FROM payroll_items pi
                JOIN employees e ON e.id = pi.employee_id
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                LEFT JOIN (
                    SELECT payroll_item_id, SUM(amount) AS paid_amount
                    FROM payroll_item_payments
                    GROUP BY payroll_item_id
                ) item_pay ON item_pay.payroll_item_id = pi.id
                WHERE pi.employee_id = :employee_id{$and}
                ORDER BY pr.run_date DESC, pr.id DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        if ($cid > 0) {
            $stmt->bindValue(':employee_cid', $cid, PDO::PARAM_INT);
            $stmt->bindValue(':run_cid', $cid, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function leaveProfileSummary(int $employeeId, ?int $year = null): array
    {
        $year = $year ?? (int) date('Y');
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = "SELECT lt.name AS leave_type_name,
                       lb.entitled_days,
                       lb.used_days,
                       (lb.entitled_days - lb.used_days) AS balance_days
                FROM leave_balances lb
                JOIN employees e ON e.id = lb.employee_id
                JOIN leave_types lt ON lt.id = lb.leave_type_id
                WHERE lb.employee_id = :employee_id AND lb.year = :year{$and}
                ORDER BY lt.name ASC";

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId, 'year' => $year];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $entitled = 0.0;
        $used = 0.0;
        $balance = 0.0;
        foreach ($rows as $row) {
            $entitled += (float) ($row['entitled_days'] ?? 0);
            $used += (float) ($row['used_days'] ?? 0);
            $balance += (float) ($row['balance_days'] ?? 0);
        }

        return [
            'year' => $year,
            'items' => $rows,
            'entitled_days' => $entitled,
            'used_days' => $used,
            'balance_days' => $balance,
        ];
    }

    public function archive(int $id, int $userId): bool
    {
        $cid = Tenant::id();
        $sql = "UPDATE employees
                SET archived_at = NOW(), archived_by = :archived_by, portal_active = 0, contract_status = 'Ended'
                WHERE id = :id AND archived_at IS NULL"
            . ($cid > 0 ? ' AND company_id = :cid' : '');

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id, 'archived_by' => $userId > 0 ? $userId : null];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function lifecycleHistory(int $employeeId): array
    {
        return (new EmployeeLifecycle())->forEmployee($employeeId);
    }
}

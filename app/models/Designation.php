<?php

declare(strict_types=1);

class Designation extends Model
{
    protected string $table = 'designations';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        if (!$this->tableExists('designations')) {
            $this->db->exec(
                "CREATE TABLE designations (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    company_id BIGINT UNSIGNED NOT NULL,
                    department_id BIGINT UNSIGNED NULL,
                    code VARCHAR(30) NOT NULL,
                    name VARCHAR(150) NOT NULL,
                    description TEXT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_designations_company_code (company_id, code),
                    UNIQUE KEY uq_designations_company_name (company_id, name),
                    KEY idx_designations_department (department_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        if (!$this->columnExists('employees', 'designation_id')) {
            $this->db->exec('ALTER TABLE employees ADD COLUMN designation_id BIGINT UNSIGNED NULL AFTER department_id');
        }

        if ($this->columnExists('employee_onboarding_requests', 'designation') && !$this->columnExists('employee_onboarding_requests', 'designation_id')) {
            $this->db->exec('ALTER TABLE employee_onboarding_requests ADD COLUMN designation_id BIGINT UNSIGNED NULL AFTER department_id');
        }

        $this->seedFromEmployees();
    }

    public function generateNextCode(): string
    {
        $cid = Tenant::id();
        $sql = "SELECT code FROM designations WHERE code REGEXP :pattern"
             . ($cid > 0 ? ' AND company_id = :cid' : '')
             . ' ORDER BY code DESC LIMIT 1';
        $params = ['pattern' => '^DES[0-9]{3,}$'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, 3)) + 1 : 1;

        return 'DES' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function activeOptions(?int $departmentId = null): array
    {
        $cid = Tenant::id();
        $where = 'WHERE is_active = 1';
        $params = [];
        if ($cid > 0) {
            $where .= ' AND company_id = :cid';
            $params['cid'] = $cid;
        }
        if ($departmentId !== null && $departmentId > 0) {
            $where .= ' AND (department_id = :department_id OR department_id IS NULL)';
            $params['department_id'] = $departmentId;
        }

        $stmt = $this->db->prepare("SELECT * FROM designations {$where} ORDER BY name ASC");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listWithDepartment(string $search = ''): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE des.company_id = :cid' : 'WHERE 1=1';
        $params = [];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        if ($search !== '') {
            $where .= ' AND (des.name LIKE :keyword OR des.code LIKE :keyword OR d.name LIKE :keyword)';
            $params['keyword'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT des.*, d.name AS department_name
             FROM designations des
             LEFT JOIN departments d ON d.id = des.department_id
             {$where}
             ORDER BY des.is_active DESC, des.name ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findName(int $id): ?string
    {
        $row = $this->find($id);
        return $row ? (string) $row['name'] : null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        return $this->valueExists('name', $name, $excludeId);
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->valueExists('code', $code, $excludeId);
    }

    private function valueExists(string $column, string $value, ?int $excludeId): bool
    {
        $cid = Tenant::id();
        $sql = "SELECT id FROM designations WHERE {$column} = :value" . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['value' => $value];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    private function seedFromEmployees(): void
    {
        $cid = Tenant::id();
        if ($cid <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT DISTINCT TRIM(designation) AS designation_name
             FROM employees
             WHERE company_id = :cid
               AND designation IS NOT NULL
               AND TRIM(designation) <> ''"
        );
        $stmt->execute(['cid' => $cid]);

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $name = trim((string) $name);
            if ($name === '' || $this->nameExists($name)) {
                continue;
            }
            $this->insert([
                'code' => $this->generateNextCode(),
                'name' => $name,
                'description' => 'Seeded from existing employee records.',
                'is_active' => 1,
            ]);
        }

        $map = $this->db->prepare(
            "UPDATE employees e
             JOIN designations des
               ON des.company_id = e.company_id
              AND LOWER(des.name) = LOWER(TRIM(e.designation))
             SET e.designation_id = des.id
             WHERE e.company_id = :cid
               AND e.designation_id IS NULL
               AND e.designation IS NOT NULL
               AND TRIM(e.designation) <> ''"
        );
        $map->execute(['cid' => $cid]);
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
        );
        $stmt->execute(['table' => $table]);

        return (int) $stmt->fetchColumn() > 0;
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
}

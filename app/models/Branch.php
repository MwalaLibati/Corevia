<?php

declare(strict_types=1);

class Branch extends Model
{
    protected string $table = 'branches';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function listWithCounts(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE b.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT b.*,
                    COUNT(e.id) AS employee_count,
                    manager.full_name AS manager_name
             FROM branches b
             LEFT JOIN employees e ON e.branch_id = b.id AND e.archived_at IS NULL
             LEFT JOIN employees manager ON manager.id = b.manager_employee_id
             {$where}
             GROUP BY b.id
             ORDER BY b.name ASC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }

    public function activeOptions(?int $companyId = null): array
    {
        $companyId = $companyId ?? Tenant::id();
        $where = $companyId > 0 ? 'WHERE company_id = :cid AND is_active = 1' : 'WHERE is_active = 1';
        $stmt = $this->db->prepare("SELECT id, name, code FROM branches {$where} ORDER BY name ASC");
        $stmt->execute($companyId > 0 ? ['cid' => $companyId] : []);

        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND b.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT b.*, COUNT(e.id) AS employee_count
             FROM branches b
             LEFT JOIN employees e ON e.branch_id = b.id AND e.archived_at IS NULL
             WHERE (b.name LIKE :keyword OR b.code LIKE :keyword OR b.city LIKE :keyword){$and}
             GROUP BY b.id
             ORDER BY b.name ASC"
        );
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function generateNextCode(): string
    {
        $cid = Tenant::id();
        $sql = "SELECT code FROM branches WHERE code REGEXP :pattern"
             . ($cid > 0 ? ' AND company_id = :cid' : '')
             . ' ORDER BY code DESC LIMIT 1';
        $params = ['pattern' => '^BR[0-9]{3,}$'];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $last = (string) ($stmt->fetchColumn() ?: '');
        $next = $last !== '' ? ((int) substr($last, 2)) + 1 : 1;

        return 'BR' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        return $this->valueExists('name', $name, $excludeId);
    }

    public function codeExists(?string $code, ?int $excludeId = null): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return $this->valueExists('code', $code, $excludeId);
    }

    public function belongsToTenant(int $branchId): bool
    {
        if ($branchId <= 0) {
            return true;
        }

        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT id FROM branches WHERE id = :id' . ($cid > 0 ? ' AND company_id = :cid' : '') . ' LIMIT 1'
        );
        $params = ['id' => $branchId];
        if ($cid > 0) {
            $params['cid'] = $cid;
        }
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS branches (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(190) NOT NULL,
                code VARCHAR(40) NULL,
                phone VARCHAR(80) NULL,
                email VARCHAR(190) NULL,
                address TEXT NULL,
                city VARCHAR(120) NULL,
                manager_employee_id BIGINT UNSIGNED NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_branches_company_id (company_id),
                KEY idx_branches_manager_employee_id (manager_employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->addColumnIfMissing('employees', 'branch_id', 'BIGINT UNSIGNED NULL AFTER department_id');
        $this->addIndexIfMissing('employees', 'idx_employees_branch_id', 'branch_id');
    }

    private function valueExists(string $column, string $value, ?int $excludeId): bool
    {
        $cid = Tenant::id();
        $sql = "SELECT id FROM branches WHERE {$column} = :value" . ($cid > 0 ? ' AND company_id = :cid' : '');
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

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column"
        );
        $stmt->execute(['table' => $table, 'column' => $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndexIfMissing(string $table, string $index, string $columns): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name"
        );
        $stmt->execute(['table' => $table, 'index_name' => $index]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        }
    }
}

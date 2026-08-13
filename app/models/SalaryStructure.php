<?php

declare(strict_types=1);

/**
 * Salary structure model for pay configuration.
 */

class SalaryStructure extends Model
{
    protected string $table = 'salary_structures';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $columns = [
            'basic_pay_source' => "VARCHAR(40) NOT NULL DEFAULT 'Fixed Salary'",
            'hourly_rate' => 'DECIMAL(12,4) NULL',
            'daily_rate' => 'DECIMAL(12,4) NULL',
            'shift_rate' => 'DECIMAL(12,4) NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists('salary_structures', $column)) {
                $this->db->exec("ALTER TABLE salary_structures ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM salary_structures WHERE (name LIKE :keyword OR grade_level LIKE :keyword)$and ORDER BY id DESC");
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM salary_structures WHERE name = :name'
             . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['name' => $name];
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

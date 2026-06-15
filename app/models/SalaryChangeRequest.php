<?php

declare(strict_types=1);

class SalaryChangeRequest extends Model
{
    protected string $table = 'salary_change_requests';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $columns = [
            'actual_basic_pay' => 'DECIMAL(12,2) NULL',
            'actual_housing_allowance' => 'DECIMAL(12,2) NULL',
            'actual_transport_allowance' => 'DECIMAL(12,2) NULL',
            'actual_other_allowances' => 'DECIMAL(12,2) NULL',
            'override_reason' => 'TEXT NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists('salary_change_requests', $column)) {
                $this->db->exec("ALTER TABLE salary_change_requests ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function listWithDetails(): array
    {
        $stmt = $this->db->prepare(
            'SELECT scr.*, e.full_name AS employee_name, e.employee_number, ss.name AS salary_structure_name, ss.grade_level,
                    ss.basic_pay AS structure_basic_pay,
                    ss.housing_allowance AS structure_housing_allowance,
                    ss.transport_allowance AS structure_transport_allowance,
                    ss.other_allowances AS structure_other_allowances
             FROM salary_change_requests scr
             JOIN employees e ON e.id = scr.employee_id
             JOIN salary_structures ss ON ss.id = scr.salary_structure_id
             WHERE scr.company_id = :cid
             ORDER BY scr.created_at DESC'
        );
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT scr.*, e.full_name AS employee_name, e.employee_number, ss.name AS salary_structure_name,
                    ss.basic_pay AS structure_basic_pay,
                    ss.housing_allowance AS structure_housing_allowance,
                    ss.transport_allowance AS structure_transport_allowance,
                    ss.other_allowances AS structure_other_allowances
             FROM salary_change_requests scr
             JOIN employees e ON e.id = scr.employee_id
             JOIN salary_structures ss ON ss.id = scr.salary_structure_id
             WHERE scr.id = :id AND scr.company_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();
        return $row ?: null;
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

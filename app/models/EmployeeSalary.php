<?php

declare(strict_types=1);

/**
 * Employee salary assignment model.
 */

class EmployeeSalary extends Model
{
    protected string $table = 'employee_salary';

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
            if (!$this->columnExists('employee_salary', $column)) {
                $this->db->exec("ALTER TABLE employee_salary ADD COLUMN {$column} {$definition}");
            }
        }
    }

    public function activeWithStructure(int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND ss.company_id = :structure_cid' : '';
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level,
                       ss.basic_pay AS structure_basic_pay,
                       ss.housing_allowance AS structure_housing_allowance,
                       ss.transport_allowance AS structure_transport_allowance,
                       ss.other_allowances AS structure_other_allowances,
                       COALESCE(es.actual_basic_pay, ss.basic_pay) AS basic_pay,
                       COALESCE(es.actual_housing_allowance, ss.housing_allowance) AS housing_allowance,
                       COALESCE(es.actual_transport_allowance, ss.transport_allowance) AS transport_allowance,
                       COALESCE(es.actual_other_allowances, ss.other_allowances) AS other_allowances
                FROM employee_salary es
                JOIN employees e ON e.id = es.employee_id
                JOIN salary_structures ss ON ss.id = es.salary_structure_id
                WHERE es.employee_id = :employee_id
                  AND es.is_active = 1' . $and . '
                ORDER BY es.effective_date DESC, es.id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['structure_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function activeWithStructureForDate(int $employeeId, string $asOfDate): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND ss.company_id = :structure_cid' : '';
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level,
                       ss.basic_pay AS structure_basic_pay,
                       ss.housing_allowance AS structure_housing_allowance,
                       ss.transport_allowance AS structure_transport_allowance,
                       ss.other_allowances AS structure_other_allowances,
                       COALESCE(es.actual_basic_pay, ss.basic_pay) AS basic_pay,
                       COALESCE(es.actual_housing_allowance, ss.housing_allowance) AS housing_allowance,
                       COALESCE(es.actual_transport_allowance, ss.transport_allowance) AS transport_allowance,
                       COALESCE(es.actual_other_allowances, ss.other_allowances) AS other_allowances
                FROM employee_salary es
                JOIN employees e ON e.id = es.employee_id
                JOIN salary_structures ss ON ss.id = es.salary_structure_id
                WHERE es.employee_id = :employee_id
                  AND es.effective_date <= :as_of_date' . $and . '
                ORDER BY es.effective_date DESC, es.id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = [
            'employee_id' => $employeeId,
            'as_of_date' => $asOfDate,
        ];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['structure_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function historyWithStructure(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND ss.company_id = :structure_cid' : '';
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level,
                       ss.basic_pay AS structure_basic_pay,
                       ss.housing_allowance AS structure_housing_allowance,
                       ss.transport_allowance AS structure_transport_allowance,
                       ss.other_allowances AS structure_other_allowances,
                       COALESCE(es.actual_basic_pay, ss.basic_pay) AS basic_pay,
                       COALESCE(es.actual_housing_allowance, ss.housing_allowance) AS housing_allowance,
                       COALESCE(es.actual_transport_allowance, ss.transport_allowance) AS transport_allowance,
                       COALESCE(es.actual_other_allowances, ss.other_allowances) AS other_allowances
                FROM employee_salary es
                JOIN employees e ON e.id = es.employee_id
                JOIN salary_structures ss ON ss.id = es.salary_structure_id
                WHERE es.employee_id = :employee_id' . $and . '
                ORDER BY es.effective_date DESC, es.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['structure_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function assignAndActivate(int $employeeId, int $salaryStructureId, string $effectiveDate, array $agreedPay = []): void
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $guard = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM employees e
                 JOIN salary_structures ss ON ss.id = :sid AND ss.company_id = e.company_id
                 WHERE e.id = :eid AND e.company_id = :cid'
            );
            $guard->execute(['eid' => $employeeId, 'sid' => $salaryStructureId, 'cid' => $cid]);
            if ((int) $guard->fetchColumn() === 0) {
                throw new RuntimeException('Employee or salary structure not found for the active tenant.');
            }
        }

        $this->db->beginTransaction();

        try {
            $deactivate = $this->db->prepare('UPDATE employee_salary SET is_active = 0 WHERE employee_id = :employee_id AND is_active = 1');
            $deactivate->execute(['employee_id' => $employeeId]);

            $insert = $this->db->prepare(
                'INSERT INTO employee_salary
                    (employee_id, salary_structure_id, effective_date, actual_basic_pay, actual_housing_allowance, actual_transport_allowance, actual_other_allowances, override_reason, is_active)
                 VALUES
                    (:employee_id, :salary_structure_id, :effective_date, :actual_basic_pay, :actual_housing_allowance, :actual_transport_allowance, :actual_other_allowances, :override_reason, 1)'
            );
            $insert->execute([
                'employee_id' => $employeeId,
                'salary_structure_id' => $salaryStructureId,
                'effective_date' => $effectiveDate,
                'actual_basic_pay' => $agreedPay['actual_basic_pay'] ?? null,
                'actual_housing_allowance' => $agreedPay['actual_housing_allowance'] ?? null,
                'actual_transport_allowance' => $agreedPay['actual_transport_allowance'] ?? null,
                'actual_other_allowances' => $agreedPay['actual_other_allowances'] ?? null,
                'override_reason' => $agreedPay['override_reason'] ?? null,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
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

<?php

declare(strict_types=1);

/**
 * Employee salary assignment model.
 */

class EmployeeSalary extends Model
{
    protected string $table = 'employee_salary';

    public function activeWithStructure(int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND ss.company_id = :structure_cid' : '';
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level
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
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level, ss.basic_pay, ss.housing_allowance, ss.transport_allowance, ss.other_allowances
                FROM employee_salary es
                JOIN employees e ON e.id = es.employee_id
                JOIN salary_structures ss ON ss.id = es.salary_structure_id
                WHERE es.employee_id = :employee_id
                  AND es.is_active = 1
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
        $sql = 'SELECT es.*, ss.name AS structure_name, ss.grade_level
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

    public function assignAndActivate(int $employeeId, int $salaryStructureId, string $effectiveDate): void
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

            $insert = $this->db->prepare('INSERT INTO employee_salary (employee_id, salary_structure_id, effective_date, is_active) VALUES (:employee_id, :salary_structure_id, :effective_date, 1)');
            $insert->execute([
                'employee_id' => $employeeId,
                'salary_structure_id' => $salaryStructureId,
                'effective_date' => $effectiveDate,
            ]);

            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }
}

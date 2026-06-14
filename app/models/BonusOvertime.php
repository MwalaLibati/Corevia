<?php

declare(strict_types=1);

/**
 * Bonus and overtime model for additional payroll items.
 */

class BonusOvertime extends Model
{
    protected string $table = 'bonuses_overtime';

    public function listWithEmployee(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE e.company_id = :cid' : '';
        $sql = 'SELECT bo.*, e.full_name AS employee_name, e.employee_number
                FROM bonuses_overtime bo
                JOIN employees e ON e.id = bo.employee_id
                ' . $where . '
                ORDER BY bo.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT bo.*, e.full_name AS employee_name, e.employee_number
                FROM bonuses_overtime bo
                JOIN employees e ON e.id = bo.employee_id
                WHERE (e.full_name LIKE :keyword
                   OR e.employee_number LIKE :keyword
                   OR bo.item_type LIKE :keyword)' . $and . '
                ORDER BY bo.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function employees(): array
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $stmt = $this->db->prepare('SELECT id, full_name, employee_number FROM employees WHERE company_id = :cid ORDER BY full_name');
            $stmt->execute(['cid' => $cid]);
            return $stmt->fetchAll();
        }
        return $this->db->query('SELECT id, full_name, employee_number FROM employees ORDER BY full_name')->fetchAll();
    }

    public function forRunAndEmployee(int $runId, int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND pr.company_id = :run_cid' : '';
        $sql = 'SELECT bo.*, e.full_name AS employee_name, e.employee_number
                FROM bonuses_overtime bo
                JOIN employees e ON e.id = bo.employee_id
                LEFT JOIN payroll_runs pr ON pr.id = bo.payroll_run_id
                WHERE bo.payroll_run_id = :run_id
                  AND bo.employee_id = :employee_id' . $and . '
                ORDER BY bo.id ASC';

        $stmt = $this->db->prepare($sql);
        $params = [
            'run_id' => $runId,
            'employee_id' => $employeeId,
        ];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['run_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

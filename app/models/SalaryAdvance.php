<?php

declare(strict_types=1);

class SalaryAdvance extends Model
{
    protected string $table = 'salary_advances';

    public function listWithDetails(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT sa.*, e.full_name AS employee_name, e.employee_number,
                    d.name AS department_name,
                    u.full_name AS approved_by_name
             FROM salary_advances sa
             JOIN employees e ON e.id = sa.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN users u ON u.id = sa.approved_by
             $where
             ORDER BY sa.created_at DESC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function forEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT sa.*, u.full_name AS approved_by_name
             FROM salary_advances sa
             JOIN employees e ON e.id = sa.employee_id
             LEFT JOIN users u ON u.id = sa.approved_by
             WHERE sa.employee_id = :eid$and
             ORDER BY sa.created_at DESC"
        );
        $params = ['eid' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function activeForEmployee(int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT sa.* FROM salary_advances sa
             JOIN employees e ON e.id = sa.employee_id
             WHERE sa.employee_id = :eid AND sa.status = 'Active'$and
             ORDER BY sa.start_date ASC LIMIT 1"
        );
        $params = ['eid' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

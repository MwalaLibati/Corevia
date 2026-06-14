<?php

declare(strict_types=1);

class SalaryChangeRequest extends Model
{
    protected string $table = 'salary_change_requests';
    protected bool $tenantScoped = true;

    public function listWithDetails(): array
    {
        $stmt = $this->db->prepare(
            'SELECT scr.*, e.full_name AS employee_name, e.employee_number, ss.name AS salary_structure_name, ss.grade_level
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
            'SELECT scr.*, e.full_name AS employee_name, e.employee_number, ss.name AS salary_structure_name
             FROM salary_change_requests scr
             JOIN employees e ON e.id = scr.employee_id
             JOIN salary_structures ss ON ss.id = scr.salary_structure_id
             WHERE scr.id = :id AND scr.company_id = :cid LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'cid' => Tenant::id()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

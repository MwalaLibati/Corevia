<?php

declare(strict_types=1);

class EmployeeGeneratedLetter extends Model
{
    protected string $table = 'employee_generated_letters';
    protected bool $tenantScoped = true;

    public function forEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT egl.*, u.full_name AS generated_by_name
             FROM employee_generated_letters egl
             LEFT JOIN users u ON u.id = egl.generated_by
             WHERE egl.company_id = :cid AND egl.employee_id = :eid
             ORDER BY egl.id DESC'
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        return $stmt->fetchAll();
    }
}

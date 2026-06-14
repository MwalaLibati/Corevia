<?php

declare(strict_types=1);

class EmployeeFinalDue extends Model
{
    protected string $table = 'employee_final_dues';
    protected bool $tenantScoped = true;

    public function latestForEmployee(int $employeeId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM employee_final_dues WHERE company_id = :cid AND employee_id = :eid ORDER BY id DESC LIMIT 1');
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

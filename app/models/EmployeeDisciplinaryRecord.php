<?php

declare(strict_types=1);

class EmployeeDisciplinaryRecord extends Model
{
    protected string $table = 'employee_disciplinary_records';
    protected bool $tenantScoped = true;

    public function forEmployee(int $employeeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT edr.*, u.full_name AS created_by_name
             FROM employee_disciplinary_records edr
             LEFT JOIN users u ON u.id = edr.created_by
             WHERE edr.company_id = :cid AND edr.employee_id = :eid
             ORDER BY edr.incident_date DESC, edr.id DESC'
        );
        $stmt->execute(['cid' => Tenant::id(), 'eid' => $employeeId]);
        return $stmt->fetchAll();
    }
}

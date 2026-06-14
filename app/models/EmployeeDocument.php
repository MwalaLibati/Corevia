<?php

declare(strict_types=1);

class EmployeeDocument extends Model
{
    protected string $table = 'employee_documents';

    public function forEmployee(int $empId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM employee_documents WHERE employee_id = :eid ORDER BY created_at DESC"
        );
        $stmt->execute(['eid' => $empId]);
        return $stmt->fetchAll();
    }

    public function allWithEmployee(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT ed.*, e.full_name AS employee_name, e.employee_number
             FROM employee_documents ed
             JOIN employees e ON e.id = ed.employee_id
             $where
             ORDER BY ed.created_at DESC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }
}

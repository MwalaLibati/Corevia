<?php

declare(strict_types=1);

/**
 * Attendance record model for daily attendance operations.
 */

class AttendanceRecord extends Model
{
    protected string $table = 'attendance_records';

    public function listWithEmployee(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' WHERE e.company_id = :cid' : '';
        $sql = 'SELECT ar.*, e.full_name AS employee_name, e.employee_number
                FROM attendance_records ar
                JOIN employees e ON e.id = ar.employee_id
                ' . $and . '
                ORDER BY ar.attendance_date DESC, ar.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT ar.*, e.full_name AS employee_name, e.employee_number
                FROM attendance_records ar
                JOIN employees e ON e.id = ar.employee_id
                WHERE (e.full_name LIKE :keyword
                   OR e.employee_number LIKE :keyword
                   OR ar.status LIKE :keyword)
                   ' . $and . '
                ORDER BY ar.attendance_date DESC, ar.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $sql = 'SELECT ar.*, e.full_name AS employee_name
                FROM attendance_records ar
                JOIN employees e ON e.id = ar.employee_id
                WHERE ar.id = :id' . $and . ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function employees(): array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT id, full_name, employee_number FROM employees'
            . ($cid > 0 ? ' WHERE company_id = :cid' : '')
            . ' ORDER BY full_name'
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function existsForEmployeeDate(int $employeeId, string $date, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM attendance_records WHERE employee_id = :employee_id AND attendance_date = :attendance_date';
        $params = ['employee_id' => $employeeId, 'attendance_date' => $date];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}

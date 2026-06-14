<?php

declare(strict_types=1);

class LeaveRequest extends Model
{
    protected string $table = 'leave_requests';

    public function listWithDetails(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? 'AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT lr.*, e.full_name AS employee_name, e.employee_number,
                    lt.name AS leave_type_name, lt.code AS leave_type_code, lt.is_paid,
                    u.full_name AS approved_by_name
             FROM leave_requests lr
             JOIN employees e  ON e.id  = lr.employee_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             LEFT JOIN users u ON u.id = lr.approved_by
             WHERE 1=1 $and
             ORDER BY lr.created_at DESC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function forEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND lt.company_id = :leave_cid' : '';
        $stmt = $this->db->prepare(
            "SELECT lr.*, lt.name AS leave_type_name, lt.code AS leave_type_code, lt.is_paid,
                    u.full_name AS approved_by_name
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             LEFT JOIN users u ON u.id = lr.approved_by
             WHERE lr.employee_id = :eid$and
             ORDER BY lr.created_at DESC"
        );
        $params = ['eid' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['leave_cid'] = $cid; }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pending(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? 'AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT lr.*, e.full_name AS employee_name, e.employee_number,
                    lt.name AS leave_type_name, lt.code AS leave_type_code
             FROM leave_requests lr
             JOIN employees e  ON e.id  = lr.employee_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.status = 'Pending' $and
             ORDER BY lr.start_date ASC"
        );
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND lt.company_id = :leave_cid' : '';
        $stmt = $this->db->prepare(
            "SELECT lr.*, e.full_name AS employee_name, e.employee_number, e.designation,
                    d.name AS department_name,
                    lt.name AS leave_type_name, lt.code AS leave_type_code, lt.is_paid,
                    u.full_name AS approved_by_name
             FROM leave_requests lr
             JOIN employees e  ON e.id  = lr.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             LEFT JOIN users u ON u.id = lr.approved_by
             WHERE lr.id = :id$and LIMIT 1"
        );
        $params = ['id' => $id];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['leave_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getBalance(int $employeeId, int $leaveTypeId, int $year): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND lt.company_id = :leave_cid' : '';
        $stmt = $this->db->prepare(
            "SELECT lb.* FROM leave_balances lb
             JOIN employees e ON e.id = lb.employee_id
             JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.employee_id = :eid AND lb.leave_type_id = :tid AND lb.year = :yr$and LIMIT 1"
        );
        $params = ['eid' => $employeeId, 'tid' => $leaveTypeId, 'yr' => $year];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['leave_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        if (!$row) {
            $ltAnd = $cid > 0 ? ' AND company_id = :cid' : '';
            $ltStmt = $this->db->prepare("SELECT days_per_year FROM leave_types WHERE id = :id$ltAnd LIMIT 1");
            $ltParams = ['id' => $leaveTypeId];
            if ($cid > 0) { $ltParams['cid'] = $cid; }
            $ltStmt->execute($ltParams);
            $lt = $ltStmt->fetch();
            return ['entitled_days' => (float)($lt['days_per_year'] ?? 0), 'used_days' => 0.0, 'balance' => (float)($lt['days_per_year'] ?? 0)];
        }

        return [
            'entitled_days' => (float)$row['entitled_days'],
            'used_days'     => (float)$row['used_days'],
            'balance'       => (float)$row['entitled_days'] - (float)$row['used_days'],
        ];
    }

    public function updateBalance(int $employeeId, int $leaveTypeId, int $year, float $days): void
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $guard = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM employees e
                 JOIN leave_types lt ON lt.id = :tid AND lt.company_id = e.company_id
                 WHERE e.id = :eid AND e.company_id = :cid'
            );
            $guard->execute(['eid' => $employeeId, 'tid' => $leaveTypeId, 'cid' => $cid]);
            if ((int) $guard->fetchColumn() === 0) {
                throw new RuntimeException('Employee or leave type not found for the active tenant.');
            }
        }

        $existing = $this->db->prepare(
            "SELECT id, entitled_days FROM leave_balances WHERE employee_id=:eid AND leave_type_id=:tid AND year=:yr LIMIT 1"
        );
        $existing->execute(['eid' => $employeeId, 'tid' => $leaveTypeId, 'yr' => $year]);
        $row = $existing->fetch();

        if ($row) {
            $this->db->prepare(
                "UPDATE leave_balances SET used_days = used_days + :days WHERE employee_id=:eid AND leave_type_id=:tid AND year=:yr"
            )->execute(['days' => $days, 'eid' => $employeeId, 'tid' => $leaveTypeId, 'yr' => $year]);
        } else {
            $ltStmt = $this->db->prepare("SELECT days_per_year FROM leave_types WHERE id = :id LIMIT 1");
            $ltStmt->execute(['id' => $leaveTypeId]);
            $lt = $ltStmt->fetch();
            $entitled = (float)($lt['days_per_year'] ?? 0);
            $this->db->prepare(
                "INSERT INTO leave_balances (employee_id, leave_type_id, year, entitled_days, used_days) VALUES (:eid,:tid,:yr,:ent,:ud)"
            )->execute(['eid' => $employeeId, 'tid' => $leaveTypeId, 'yr' => $year, 'ent' => $entitled, 'ud' => $days]);
        }
    }
}

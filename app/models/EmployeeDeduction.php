<?php

declare(strict_types=1);

/**
 * Employee deduction assignment model.
 */

class EmployeeDeduction extends Model
{
    protected string $table = 'employee_deductions';

    private const MANAGED_STATUTORY_CODES = ['PAYE', 'NAPSA', 'NHIMA'];

    public static function isManagedStatutoryCode(?string $code): bool
    {
        return in_array(strtoupper(trim((string) $code)), self::MANAGED_STATUTORY_CODES, true);
    }

    public function activeForEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type, dt.default_value
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                WHERE ed.employee_id = :employee_id
                  AND ed.is_active = 1' . $and . '
                ORDER BY ed.start_date DESC, ed.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function historyForEmployee(int $employeeId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type, dt.default_value
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                WHERE ed.employee_id = :employee_id' . $and . '
                ORDER BY ed.is_active DESC, ed.start_date DESC, ed.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['employee_id' => $employeeId];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listWithRelations(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, e.full_name AS employee_name, e.employee_number,
                       dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                ' . $where . '
                ORDER BY ed.is_active DESC, ed.start_date DESC, ed.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['employee_cid' => $cid, 'deduction_cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, e.full_name AS employee_name, e.employee_number,
                       dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                WHERE (e.full_name LIKE :keyword
                   OR e.employee_number LIKE :keyword
                   OR dt.name LIKE :keyword
                   OR dt.code LIKE :keyword)' . $and . '
                ORDER BY ed.is_active DESC, ed.start_date DESC, ed.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function employees(): array
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $stmt = $this->db->prepare('SELECT id, full_name, employee_number FROM employees WHERE company_id = :cid ORDER BY full_name ASC');
            $stmt->execute(['cid' => $cid]);
            return $stmt->fetchAll();
        }
        return $this->db->query('SELECT id, full_name, employee_number FROM employees ORDER BY full_name ASC')->fetchAll();
    }

    public function deductionTypes(): array
    {
        $cid = Tenant::id();
        if ($cid > 0) {
            $stmt = $this->db->prepare("SELECT id, name, code, is_statutory, calculation_type, default_value FROM deduction_types WHERE company_id = :cid AND UPPER(code) NOT IN ('PAYE','NAPSA','NHIMA') ORDER BY name ASC");
            $stmt->execute(['cid' => $cid]);
            return $stmt->fetchAll();
        }
        return $this->db->query("SELECT id, name, code, is_statutory, calculation_type, default_value FROM deduction_types WHERE UPPER(code) NOT IN ('PAYE','NAPSA','NHIMA') ORDER BY name ASC")->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, e.full_name AS employee_name, e.employee_number,
                       dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                WHERE ed.id = :id' . $and . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function activeForEmployeeAtDate(int $employeeId, string $asOfDate): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT ed.*, dt.name AS deduction_name, dt.code AS deduction_code, dt.is_statutory, dt.calculation_type, dt.default_value
                FROM employee_deductions ed
                JOIN employees e ON e.id = ed.employee_id
                JOIN deduction_types dt ON dt.id = ed.deduction_type_id
                WHERE ed.employee_id = :employee_id
                  AND ed.is_active = 1
                  AND (ed.start_date IS NULL OR ed.start_date <= :start_date)
                  AND (ed.end_date IS NULL OR ed.end_date >= :end_date)' . $and . '
                ORDER BY ed.id DESC';

        $stmt = $this->db->prepare($sql);
        $params = [
            'employee_id' => $employeeId,
            'start_date' => $asOfDate,
            'end_date' => $asOfDate,
        ];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function applicableForEmployeeAtDate(int $employeeId, string $asOfDate): array
    {
        return $this->activeForEmployeeAtDate($employeeId, $asOfDate);
    }

    public function calculateTotalForEmployee(int $employeeId, float $grossPay, string $asOfDate): float
    {
        $deductions = $this->applicableForEmployeeAtDate($employeeId, $asOfDate);

        $assignedTypeIds = array_map(static fn(array $d): int => (int) $d['deduction_type_id'], $deductions);

        $cid = Tenant::id();
        $autoSql = "SELECT id, calculation_type, default_value AS amount FROM deduction_types WHERE auto_apply = 1 AND UPPER(code) NOT IN ('PAYE','NAPSA','NHIMA')";
        $autoParams = [];
        if ($cid > 0) {
            $autoSql .= ' AND company_id = ?';
            $autoParams[] = $cid;
        }
        if (!empty($assignedTypeIds)) {
            $placeholders = implode(',', array_fill(0, count($assignedTypeIds), '?'));
            $autoSql .= " AND id NOT IN ($placeholders)";
        }
        $autoStmt = $this->db->prepare($autoSql);
        $autoStmt->execute(array_merge($autoParams, $assignedTypeIds ?: []));
        foreach ($autoStmt->fetchAll() as $auto) {
            $deductions[] = ['calculation_type' => $auto['calculation_type'], 'amount' => $auto['amount'], 'default_value' => $auto['amount']];
        }

        $total = 0.0;
        foreach ($deductions as $deduction) {
            if (self::isManagedStatutoryCode($deduction['deduction_code'] ?? null)) {
                continue;
            }

            $calculationType = (string) ($deduction['calculation_type'] ?? 'Fixed');
            $rateOrAmount    = (float) ($deduction['amount'] ?? 0);

            if ($rateOrAmount <= 0.0 && isset($deduction['default_value'])) {
                $rateOrAmount = (float) $deduction['default_value'];
            }

            if ($calculationType === 'Percent') {
                $total += $grossPay * ($rateOrAmount / 100);
                continue;
            }

            $total += $rateOrAmount;
        }

        return $total;
    }

    public function activeForEmployeeType(int $employeeId, int $deductionTypeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :employee_cid AND dt.company_id = :deduction_cid' : '';
        $sql = 'SELECT employee_deductions.* FROM employee_deductions
                JOIN employees e ON e.id = employee_deductions.employee_id
                JOIN deduction_types dt ON dt.id = employee_deductions.deduction_type_id
                WHERE employee_deductions.employee_id = :employee_id
                  AND employee_deductions.deduction_type_id = :deduction_type_id' . $and . '
                ORDER BY employee_deductions.is_active DESC, employee_deductions.start_date DESC, employee_deductions.id DESC
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = [
            'employee_id' => $employeeId,
            'deduction_type_id' => $deductionTypeId,
        ];
        if ($cid > 0) { $params['employee_cid'] = $cid; $params['deduction_cid'] = $cid; }
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function assignAndActivate(
        int $employeeId,
        int $deductionTypeId,
        float $amount,
        ?string $startDate,
        ?string $endDate
    ): void {
        $cid = Tenant::id();
        if ($cid > 0) {
            $guard = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM employees e
                 JOIN deduction_types dt ON dt.id = :did AND dt.company_id = e.company_id
                 WHERE e.id = :eid AND e.company_id = :cid'
            );
            $guard->execute(['eid' => $employeeId, 'did' => $deductionTypeId, 'cid' => $cid]);
            if ((int) $guard->fetchColumn() === 0) {
                throw new RuntimeException('Employee or deduction type not found for the active tenant.');
            }
        }

        $this->db->beginTransaction();

        try {
            $deactivate = $this->db->prepare('UPDATE employee_deductions SET is_active = 0 WHERE employee_id = :employee_id AND deduction_type_id = :deduction_type_id AND is_active = 1');
            $deactivate->execute([
                'employee_id' => $employeeId,
                'deduction_type_id' => $deductionTypeId,
            ]);

            $insert = $this->db->prepare('INSERT INTO employee_deductions (employee_id, deduction_type_id, amount, start_date, end_date, is_active) VALUES (:employee_id, :deduction_type_id, :amount, :start_date, :end_date, 1)');
            $insert->execute([
                'employee_id' => $employeeId,
                'deduction_type_id' => $deductionTypeId,
                'amount' => $amount,
                'start_date' => $startDate,
                'end_date' => $endDate,
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

<?php

declare(strict_types=1);

class AttendancePayrollRule extends Model
{
    protected string $table = 'attendance_payroll_rule_sets';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS attendance_payroll_rule_sets (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL DEFAULT 'Default Attendance Payroll Policy',
                payroll_mode ENUM('Fixed Salary Only','Fixed Salary + Attendance Adjustments','Attendance-Based Payroll','Mixed') NOT NULL DEFAULT 'Fixed Salary Only',
                attendance_impact_enabled TINYINT(1) NOT NULL DEFAULT 0,
                effective_from DATE NOT NULL,
                effective_to DATE NULL,
                standard_hours_per_day DECIMAL(6,2) NOT NULL DEFAULT 8.00,
                standard_days_per_month DECIMAL(6,2) NOT NULL DEFAULT 26.00,
                standard_start_time TIME NULL DEFAULT '08:00:00',
                grace_minutes INT NOT NULL DEFAULT 15,
                late_deduction_enabled TINYINT(1) NOT NULL DEFAULT 0,
                late_rounding_minutes INT NOT NULL DEFAULT 15,
                absence_deduction_enabled TINYINT(1) NOT NULL DEFAULT 0,
                absence_deduction_method ENUM('Daily Rate','Hourly Rate') NOT NULL DEFAULT 'Daily Rate',
                overtime_enabled TINYINT(1) NOT NULL DEFAULT 0,
                overtime_requires_approval TINYINT(1) NOT NULL DEFAULT 1,
                overtime_min_minutes INT NOT NULL DEFAULT 30,
                overtime_rounding_minutes INT NOT NULL DEFAULT 15,
                normal_overtime_multiplier DECIMAL(6,2) NOT NULL DEFAULT 1.50,
                weekend_overtime_multiplier DECIMAL(6,2) NOT NULL DEFAULT 2.00,
                holiday_overtime_multiplier DECIMAL(6,2) NOT NULL DEFAULT 2.00,
                night_shift_allowance_enabled TINYINT(1) NOT NULL DEFAULT 0,
                night_shift_allowance_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                notes TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_aprs_company_effective (company_id, effective_from, effective_to),
                KEY idx_aprs_company_active (company_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS attendance_payroll_inputs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                payroll_run_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                rule_set_id BIGINT UNSIGNED NULL,
                input_type ENUM('Earning','Deduction','Information') NOT NULL,
                code VARCHAR(40) NOT NULL,
                label VARCHAR(150) NOT NULL,
                quantity DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
                rate DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
                amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                source_summary_json JSON NULL,
                status ENUM('Preview','Approved','Locked','Excluded') NOT NULL DEFAULT 'Preview',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_att_pay_input (company_id, payroll_run_id, employee_id, code),
                KEY idx_att_pay_input_run (company_id, payroll_run_id, status),
                KEY idx_att_pay_input_employee (employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addColumnIfMissing('employees', 'pay_calculation_method', "VARCHAR(40) NOT NULL DEFAULT 'Fixed Monthly Salary'");
    }

    public function activeForDate(string $date): array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            "SELECT * FROM attendance_payroll_rule_sets
             WHERE company_id = :cid
               AND is_active = 1
               AND effective_from <= :date_from
               AND (effective_to IS NULL OR effective_to >= :date_to)
             ORDER BY effective_from DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute(['cid' => $cid, 'date_from' => $date, 'date_to' => $date]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $id = $this->insert([
            'company_id' => $cid,
            'name' => 'Default Attendance Payroll Policy',
            'payroll_mode' => 'Fixed Salary Only',
            'attendance_impact_enabled' => 0,
            'effective_from' => date('Y-01-01'),
        ]);

        return $this->find($id) ?: [];
    }

    public function listForCompany(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM attendance_payroll_rule_sets WHERE company_id = :cid ORDER BY effective_from DESC, id DESC'
        );
        $stmt->execute(['cid' => Tenant::id()]);
        return $stmt->fetchAll();
    }

    public function updateRule(int $id, array $data): void
    {
        $data['attendance_impact_enabled'] = !empty($data['attendance_impact_enabled']) ? 1 : 0;
        $data['late_deduction_enabled'] = !empty($data['late_deduction_enabled']) ? 1 : 0;
        $data['absence_deduction_enabled'] = !empty($data['absence_deduction_enabled']) ? 1 : 0;
        $data['overtime_enabled'] = !empty($data['overtime_enabled']) ? 1 : 0;
        $data['overtime_requires_approval'] = !empty($data['overtime_requires_approval']) ? 1 : 0;
        $data['night_shift_allowance_enabled'] = !empty($data['night_shift_allowance_enabled']) ? 1 : 0;
        $this->update($id, $data);
    }

    public function previewInputsForRun(array $run): array
    {
        return $this->buildInputsForRun($run);
    }

    public function persistApprovedInputsForRun(array $run): array
    {
        $inputs = $this->buildInputsForRun($run);
        $runId = (int) ($run['id'] ?? 0);
        $cid = Tenant::id();

        $this->db->prepare('DELETE FROM attendance_payroll_inputs WHERE company_id = :cid AND payroll_run_id = :run_id AND status <> "Locked"')
            ->execute(['cid' => $cid, 'run_id' => $runId]);

        $insert = $this->db->prepare(
            'INSERT INTO attendance_payroll_inputs
                (company_id, payroll_run_id, employee_id, rule_set_id, input_type, code, label, quantity, rate, amount, source_summary_json, status)
             VALUES
                (:company_id, :payroll_run_id, :employee_id, :rule_set_id, :input_type, :code, :label, :quantity, :rate, :amount, :source_summary_json, "Approved")
             ON DUPLICATE KEY UPDATE
                input_type = VALUES(input_type), label = VALUES(label), quantity = VALUES(quantity), rate = VALUES(rate),
                amount = VALUES(amount), source_summary_json = VALUES(source_summary_json), status = "Approved"'
        );

        foreach ($inputs['by_employee'] as $employeeInputs) {
            foreach ($employeeInputs as $input) {
                $insert->execute([
                    'company_id' => $cid,
                    'payroll_run_id' => $runId,
                    'employee_id' => (int) $input['employee_id'],
                    'rule_set_id' => $input['rule_set_id'] ?? null,
                    'input_type' => (string) $input['input_type'],
                    'code' => (string) $input['code'],
                    'label' => (string) $input['label'],
                    'quantity' => (float) $input['quantity'],
                    'rate' => (float) $input['rate'],
                    'amount' => (float) $input['amount'],
                    'source_summary_json' => json_encode($input['summary'] ?? [], JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        return $inputs;
    }

    public function inputsForRun(int $runId): array
    {
        $stmt = $this->db->prepare(
            "SELECT api.*, e.full_name AS employee_name, e.employee_number
             FROM attendance_payroll_inputs api
             JOIN employees e ON e.id = api.employee_id
             WHERE api.company_id = :cid AND api.payroll_run_id = :run_id
             ORDER BY e.full_name ASC, api.input_type ASC, api.code ASC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'run_id' => $runId]);
        return $stmt->fetchAll();
    }

    public function lockInputsForRun(int $runId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE attendance_payroll_inputs
             SET status = 'Locked'
             WHERE company_id = :cid AND payroll_run_id = :run_id AND status = 'Approved'"
        );
        $stmt->execute(['cid' => Tenant::id(), 'run_id' => $runId]);
    }

    private function buildInputsForRun(array $run): array
    {
        $period = substr((string) ($run['pay_period'] ?? ''), 0, 7);
        if (!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', $period)) {
            return ['enabled' => false, 'rule' => [], 'by_employee' => [], 'totals' => ['earnings' => 0.0, 'deductions' => 0.0]];
        }

        $periodStart = $period . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $rule = $this->activeForDate($periodEnd);
        $enabled = !empty($rule['attendance_impact_enabled'])
            && (string) ($rule['payroll_mode'] ?? 'Fixed Salary Only') !== 'Fixed Salary Only';

        if (!$enabled) {
            return ['enabled' => false, 'rule' => $rule, 'by_employee' => [], 'totals' => ['earnings' => 0.0, 'deductions' => 0.0]];
        }

        $salaryModel = new EmployeeSalary();
        $recordsByEmployee = $this->attendanceRecordsByEmployee($periodStart, $periodEnd);
        $byEmployee = [];
        $totals = ['earnings' => 0.0, 'deductions' => 0.0];

        foreach ($recordsByEmployee as $employeeId => $records) {
            $payMethod = (string) ($records[0]['pay_calculation_method'] ?? 'Fixed Monthly Salary');
            if ((string) ($rule['payroll_mode'] ?? '') === 'Mixed' && $payMethod === 'Fixed Monthly Salary') {
                continue;
            }

            $salary = $salaryModel->activeWithStructureForDate((int) $employeeId, $periodEnd);
            if (!$salary) {
                continue;
            }

            $basic = (float) ($salary['basic_pay'] ?? 0);
            $standardDays = max(1.0, (float) ($rule['standard_days_per_month'] ?? 26));
            $standardHours = max(1.0, (float) ($rule['standard_hours_per_day'] ?? 8));
            $dailyRate = round($basic / $standardDays, 4);
            $hourlyRate = round($dailyRate / $standardHours, 4);
            $summary = $this->summarizeRecords($records, $rule);
            $inputs = [];

            if (!empty($rule['overtime_enabled']) && $summary['overtime_minutes'] >= (int) ($rule['overtime_min_minutes'] ?? 30)) {
                $roundedMinutes = $this->roundMinutes($summary['overtime_minutes'], (int) ($rule['overtime_rounding_minutes'] ?? 15));
                $hours = round($roundedMinutes / 60, 4);
                $rate = round($hourlyRate * (float) ($rule['normal_overtime_multiplier'] ?? 1.5), 4);
                $amount = round($hours * $rate, 2);
                if ($amount > 0) {
                    $inputs[] = $this->inputLine((int) $employeeId, (int) $rule['id'], 'Earning', 'ATT-OT', 'Attendance Overtime', $hours, $rate, $amount, $summary);
                    $totals['earnings'] += $amount;
                }
            }

            if (!empty($rule['late_deduction_enabled']) && $summary['late_minutes'] > 0) {
                $roundedMinutes = $this->roundMinutes($summary['late_minutes'], (int) ($rule['late_rounding_minutes'] ?? 15));
                $hours = round($roundedMinutes / 60, 4);
                $amount = round($hours * $hourlyRate, 2);
                if ($amount > 0) {
                    $inputs[] = $this->inputLine((int) $employeeId, (int) $rule['id'], 'Deduction', 'ATT-LATE', 'Late Coming Deduction', $hours, $hourlyRate, $amount, $summary);
                    $totals['deductions'] += $amount;
                }
            }

            if (!empty($rule['absence_deduction_enabled']) && $summary['absent_days'] > 0) {
                $method = (string) ($rule['absence_deduction_method'] ?? 'Daily Rate');
                $quantity = $method === 'Hourly Rate' ? $summary['absent_days'] * $standardHours : $summary['absent_days'];
                $rate = $method === 'Hourly Rate' ? $hourlyRate : $dailyRate;
                $amount = round($quantity * $rate, 2);
                if ($amount > 0) {
                    $inputs[] = $this->inputLine((int) $employeeId, (int) $rule['id'], 'Deduction', 'ATT-ABS', 'Unapproved Absence Deduction', $quantity, $rate, $amount, $summary);
                    $totals['deductions'] += $amount;
                }
            }

            if ($inputs !== []) {
                $byEmployee[(int) $employeeId] = $inputs;
            }
        }

        $totals['earnings'] = round($totals['earnings'], 2);
        $totals['deductions'] = round($totals['deductions'], 2);

        return ['enabled' => true, 'rule' => $rule, 'by_employee' => $byEmployee, 'totals' => $totals];
    }

    private function attendanceRecordsByEmployee(string $start, string $end): array
    {
        $stmt = $this->db->prepare(
            "SELECT ar.*, COALESCE(e.pay_calculation_method, 'Fixed Monthly Salary') AS pay_calculation_method
             FROM attendance_records ar
             JOIN employees e ON e.id = ar.employee_id
             WHERE e.company_id = :cid
               AND ar.attendance_date BETWEEN :start_date AND :end_date
             ORDER BY ar.employee_id ASC, ar.attendance_date ASC"
        );
        $stmt->execute(['cid' => Tenant::id(), 'start_date' => $start, 'end_date' => $end]);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['employee_id']][] = $row;
        }

        return $rows;
    }

    private function summarizeRecords(array $records, array $rule): array
    {
        $summary = [
            'records' => count($records),
            'present_days' => 0,
            'leave_days' => 0,
            'absent_days' => 0,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
        ];
        $standardMinutes = (int) round(max(1.0, (float) ($rule['standard_hours_per_day'] ?? 8)) * 60);
        $startTime = (string) ($rule['standard_start_time'] ?? '08:00:00');
        $grace = (int) ($rule['grace_minutes'] ?? 15);

        foreach ($records as $record) {
            $status = (string) ($record['status'] ?? 'Present');
            if ($status === 'Absent') {
                $summary['absent_days']++;
                continue;
            }
            if ($status === 'Leave') {
                $summary['leave_days']++;
                continue;
            }

            $summary['present_days']++;
            $worked = $this->workedMinutes($record);
            $summary['worked_minutes'] += $worked;
            if ($worked > $standardMinutes) {
                $summary['overtime_minutes'] += $worked - $standardMinutes;
            }

            $late = $this->lateMinutes($record, $startTime, $grace);
            if ($status === 'Late' || $late > 0) {
                $summary['late_minutes'] += $late;
            }
        }

        return $summary;
    }

    private function workedMinutes(array $record): int
    {
        if (empty($record['check_in']) || empty($record['check_out'])) {
            return 0;
        }
        $date = (string) ($record['attendance_date'] ?? date('Y-m-d'));
        $in = strtotime($date . ' ' . (string) $record['check_in']);
        $out = strtotime($date . ' ' . (string) $record['check_out']);
        if ($in === false || $out === false) {
            return 0;
        }
        if ($out < $in) {
            $out += 86400;
        }
        return max(0, (int) floor(($out - $in) / 60));
    }

    private function lateMinutes(array $record, string $startTime, int $graceMinutes): int
    {
        if (empty($record['check_in'])) {
            return 0;
        }
        $date = (string) ($record['attendance_date'] ?? date('Y-m-d'));
        $expected = strtotime($date . ' ' . $startTime);
        $actual = strtotime($date . ' ' . (string) $record['check_in']);
        if ($expected === false || $actual === false) {
            return 0;
        }
        return max(0, (int) floor(($actual - $expected) / 60) - $graceMinutes);
    }

    private function roundMinutes(int $minutes, int $rounding): int
    {
        $rounding = max(1, $rounding);
        return (int) (ceil($minutes / $rounding) * $rounding);
    }

    private function inputLine(int $employeeId, int $ruleId, string $type, string $code, string $label, float $quantity, float $rate, float $amount, array $summary): array
    {
        return [
            'employee_id' => $employeeId,
            'rule_set_id' => $ruleId,
            'input_type' => $type,
            'code' => $code,
            'label' => $label,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => round($amount, 2),
            'summary' => $summary,
        ];
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}

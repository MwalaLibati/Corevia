<?php

declare(strict_types=1);

/**
 * Payroll run model for period run scaffolding.
 */

class PayrollRun extends Model
{
    protected string $table = 'payroll_runs';
    protected bool $tenantScoped = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureHistoricalPayrollSchema();
    }

    private function ensureHistoricalPayrollSchema(): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $stmt->execute(['table' => 'payroll_runs', 'column' => 'proration_mode']);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE payroll_runs ADD COLUMN proration_mode VARCHAR(30) NOT NULL DEFAULT 'Full Month'");
        }
    }

    public function listWithDetails(): array
    {
        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE pr.company_id = :cid' : '';
        $sql = "SELECT pr.*, u.full_name AS created_by_name,
                       COUNT(pi.id) AS item_count
                FROM payroll_runs pr
                LEFT JOIN users u ON u.id = pr.created_by
                LEFT JOIN payroll_items pi ON pi.payroll_run_id = pr.id
                $where
                GROUP BY pr.id, pr.pay_period, pr.run_date, pr.status, pr.total_gross, pr.total_deductions, pr.total_net, pr.created_by, pr.approved_by_hr, pr.approved_by_finance, pr.approved_by_admin, pr.created_at, pr.updated_at, u.full_name
                ORDER BY pr.run_date DESC, pr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :cid' : '';
        $sql = 'SELECT pr.*, u.full_name AS created_by_name,
                       COUNT(pi.id) AS item_count
                FROM payroll_runs pr
                LEFT JOIN users u ON u.id = pr.created_by
                LEFT JOIN payroll_items pi ON pi.payroll_run_id = pr.id
                WHERE pr.id = :id' . $and . '
                GROUP BY pr.id, pr.pay_period, pr.run_date, pr.status, pr.total_gross, pr.total_deductions, pr.total_net, pr.created_by, pr.approved_by_hr, pr.approved_by_finance, pr.approved_by_admin, pr.created_at, pr.updated_at, u.full_name
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = ['id' => $id];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function itemsForRun(int $runId): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :cid' : '';
        $paymentJoin = $this->tableExists('payroll_item_payments')
            ? 'LEFT JOIN (
                    SELECT payroll_item_id, SUM(amount) AS paid_amount
                    FROM payroll_item_payments
                    GROUP BY payroll_item_id
                ) pay ON pay.payroll_item_id = pi.id'
            : '';
        $paidSelect = $this->tableExists('payroll_item_payments')
            ? 'COALESCE(pay.paid_amount, 0) AS paid_amount,
                       GREATEST(0, pi.net_pay - COALESCE(pay.paid_amount, 0)) AS balance_due'
            : '0 AS paid_amount,
                       pi.net_pay AS balance_due';

        $sql = 'SELECT pi.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                       ' . $paidSelect . '
                FROM payroll_items pi
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                JOIN employees e ON e.id = pi.employee_id
                ' . $paymentJoin . '
                WHERE pi.payroll_run_id = :run_id' . $and . '
                ORDER BY e.full_name ASC, e.employee_number ASC';

        $stmt = $this->db->prepare($sql);
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function itemForRunAndEmployee(int $runId, int $employeeId): ?array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :run_cid AND e.company_id = :employee_cid' : '';
        $sql = 'SELECT pi.*, e.full_name AS employee_name, e.employee_number, e.email AS employee_email,
                       e.designation, e.bank_name, e.bank_account_number,
                       d.name AS department_name,
                       pr.pay_period, pr.run_date, pr.status AS run_status,
                       pr.total_gross AS run_total_gross,
                       pr.total_deductions AS run_total_deductions,
                       pr.total_net AS run_total_net,
                       COALESCE(pay.paid_amount, 0) AS paid_amount,
                       GREATEST(0, pi.net_pay - COALESCE(pay.paid_amount, 0)) AS balance_due
                FROM payroll_items pi
                JOIN employees e ON e.id = pi.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                LEFT JOIN (
                    SELECT payroll_item_id, SUM(amount) AS paid_amount
                    FROM payroll_item_payments
                    GROUP BY payroll_item_id
                ) pay ON pay.payroll_item_id = pi.id
                WHERE pi.payroll_run_id = :run_id
                  AND pi.employee_id = :employee_id' . $and . '
                LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $params = [
            'run_id' => $runId,
            'employee_id' => $employeeId,
        ];
        if ($cid > 0) { $params['run_cid'] = $cid; $params['employee_cid'] = $cid; }
        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deductionLinesForItem(int $payrollItemId): array
    {
        $this->ensureAdjustmentSchema();

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pid.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT pid.*
             FROM payroll_item_deductions pid
             WHERE pid.payroll_item_id = :item_id
               AND pid.deduction_category <> 'statutory_employer'$and
             ORDER BY pid.id ASC"
        );
        $params = ['item_id' => $payrollItemId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return array_map(static fn(array $row): array => [
            'label' => trim((string) ($row['deduction_name'] ?? 'Deduction') . (!empty($row['deduction_code']) ? ' (' . (string) $row['deduction_code'] . ')' : '')),
            'amount' => (float) ($row['amount'] ?? 0),
            'category' => (string) ($row['deduction_category'] ?? ''),
        ], $stmt->fetchAll());
    }

    public function adjustmentsForRun(int $runId): array
    {
        $this->ensureAdjustmentSchema();

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pra.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT pra.*, e.full_name AS employee_name, e.employee_number, u.full_name AS created_by_name
             FROM payroll_run_adjustments pra
             JOIN employees e ON e.id = pra.employee_id
             LEFT JOIN users u ON u.id = pra.created_by
             WHERE pra.payroll_run_id = :run_id$and
             ORDER BY pra.id DESC"
        );
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function addEmployeeDeductionAdjustment(int $runId, int $employeeId, string $label, float $amount, string $reason, int $userId): void
    {
        $this->ensureAdjustmentSchema();

        $run = $this->find($runId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }
        if ((int) ($run['is_locked'] ?? 0) === 1 || !in_array((string) ($run['status'] ?? ''), ['Draft'], true)) {
            throw new RuntimeException('Custom payroll deductions can only be added while the run is in Draft and unlocked.');
        }
        if ($amount <= 0) {
            throw new RuntimeException('Deduction amount must be greater than zero.');
        }

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :run_cid AND e.company_id = :employee_cid' : '';
        $itemStmt = $this->db->prepare(
            "SELECT pi.*
             FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             JOIN employees e ON e.id = pi.employee_id
             WHERE pi.payroll_run_id = :run_id
               AND pi.employee_id = :employee_id$and
             LIMIT 1"
        );
        $params = ['run_id' => $runId, 'employee_id' => $employeeId];
        if ($cid > 0) { $params['run_cid'] = $cid; $params['employee_cid'] = $cid; }
        $itemStmt->execute($params);
        $item = $itemStmt->fetch();
        if (!$item) {
            throw new RuntimeException('Payroll item not found for the selected employee. Generate payroll items first.');
        }

        $label = trim($label);
        $reason = trim($reason);
        if ($label === '' || $reason === '') {
            throw new RuntimeException('Deduction name and reason are required.');
        }

        $this->db->beginTransaction();
        try {
            $insertAdj = $this->db->prepare(
                "INSERT INTO payroll_run_adjustments
                    (company_id, payroll_run_id, payroll_item_id, employee_id, adjustment_type, label, amount, reason, created_by)
                 VALUES
                    (:company_id, :payroll_run_id, :payroll_item_id, :employee_id, 'Deduction', :label, :amount, :reason, :created_by)"
            );
            $insertAdj->execute([
                'company_id' => $cid,
                'payroll_run_id' => $runId,
                'payroll_item_id' => (int) $item['id'],
                'employee_id' => $employeeId,
                'label' => $label,
                'amount' => round($amount, 2),
                'reason' => $reason,
                'created_by' => $userId ?: null,
            ]);
            $adjustmentId = (int) $this->db->lastInsertId();

            $deductionCode = 'ADJ' . str_pad((string) $adjustmentId, 5, '0', STR_PAD_LEFT);
            $this->db->prepare(
                'INSERT INTO payroll_item_deductions
                    (company_id, payroll_run_id, payroll_item_id, employee_id, deduction_code, deduction_name, deduction_category, calculation_type, calculation_base, rate_percent, amount, meta_json)
                 VALUES
                    (:company_id, :payroll_run_id, :payroll_item_id, :employee_id, :deduction_code, :deduction_name, :deduction_category, :calculation_type, :calculation_base, NULL, :amount, :meta_json)'
            )->execute([
                'company_id' => $cid,
                'payroll_run_id' => $runId,
                'payroll_item_id' => (int) $item['id'],
                'employee_id' => $employeeId,
                'deduction_code' => $deductionCode,
                'deduction_name' => $label,
                'deduction_category' => 'employee_specific',
                'calculation_type' => 'Fixed',
                'calculation_base' => (float) $item['gross_pay'],
                'amount' => round($amount, 2),
                'meta_json' => json_encode(['source' => 'payroll_run_adjustment', 'adjustment_id' => $adjustmentId, 'reason' => $reason], JSON_UNESCAPED_SLASHES),
            ]);

            $newDeductions = round((float) $item['total_deductions'] + $amount, 2);
            $newNet = max(0.0, round((float) $item['gross_pay'] - $newDeductions, 2));
            $this->db->prepare('UPDATE payroll_items SET total_deductions = :deductions, net_pay = :net WHERE id = :id')
                ->execute(['deductions' => $newDeductions, 'net' => $newNet, 'id' => (int) $item['id']]);

            $this->refreshRunTotals($runId);
            $this->recordCalculationAudit($runId, (int) $item['id'], $employeeId, 'custom_deduction_added', [
                'gross_pay' => (float) $item['gross_pay'],
                'total_deductions' => $newDeductions,
                'net_pay' => $newNet,
                'adjustment' => ['code' => $deductionCode, 'label' => $label, 'amount' => round($amount, 2), 'reason' => $reason],
            ], $userId ?: null);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function generatePayrollItems(int $runId): array
    {
        $run = $this->find($runId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        if ((int) ($run['is_locked'] ?? 0) === 1) {
            throw new RuntimeException('This payroll run is locked and cannot be regenerated. Create a correction run instead.');
        }

        if (!in_array((string) ($run['status'] ?? ''), ['Draft'], true)) {
            throw new RuntimeException('Only draft payroll runs can be generated or recalculated.');
        }

        $calculation = $this->calculatePayrollItems($run);
        $cid = Tenant::id();
        $userId = (int) (current_user()['id'] ?? 0) ?: null;

        $this->db->beginTransaction();

        try {
            $this->db->prepare('DELETE FROM payroll_item_deductions WHERE payroll_run_id = :run_id')->execute(['run_id' => $runId]);
            $this->db->prepare('DELETE FROM payroll_items WHERE payroll_run_id = :run_id')->execute(['run_id' => $runId]);

            $resetBonuses = $this->db->prepare(
                'UPDATE bonuses_overtime bo
                 JOIN employees e ON e.id = bo.employee_id
                 SET bo.payroll_run_id = NULL
                 WHERE bo.payroll_run_id = :run_id' . ($cid > 0 ? ' AND e.company_id = :cid' : '')
            );
            $resetParams = ['run_id' => $runId];
            if ($cid > 0) { $resetParams['cid'] = $cid; }
            $resetBonuses->execute($resetParams);

            $itemInsert = $this->db->prepare(
                'INSERT INTO payroll_items (payroll_run_id, employee_id, gross_pay, total_deductions, net_pay, generated_at)
                 VALUES (:payroll_run_id, :employee_id, :gross_pay, :total_deductions, :net_pay, NOW())'
            );
            $deductionInsert = $this->db->prepare(
                'INSERT INTO payroll_item_deductions
                    (company_id, payroll_run_id, payroll_item_id, employee_id, deduction_code, deduction_name, deduction_category, calculation_type, calculation_base, rate_percent, amount, meta_json)
                 VALUES
                    (:company_id, :payroll_run_id, :payroll_item_id, :employee_id, :deduction_code, :deduction_name, :deduction_category, :calculation_type, :calculation_base, :rate_percent, :amount, :meta_json)'
            );
            $bonusLink = $this->db->prepare('UPDATE bonuses_overtime SET payroll_run_id = :run_id WHERE id = :id');

            foreach ($calculation['items'] as $row) {
                $itemInsert->execute([
                    'payroll_run_id' => $runId,
                    'employee_id' => (int) $row['employee_id'],
                    'gross_pay' => (float) $row['gross_pay'],
                    'total_deductions' => (float) $row['total_deductions'],
                    'net_pay' => (float) $row['net_pay'],
                ]);
                $itemId = (int) $this->db->lastInsertId();

                foreach ($row['deduction_lines'] as $line) {
                    $deductionInsert->execute([
                        'company_id' => $cid,
                        'payroll_run_id' => $runId,
                        'payroll_item_id' => $itemId,
                        'employee_id' => (int) $row['employee_id'],
                        'deduction_code' => (string) $line['code'],
                        'deduction_name' => (string) $line['name'],
                        'deduction_category' => (string) $line['category'],
                        'calculation_type' => $line['calculation_type'] ?? null,
                        'calculation_base' => (float) ($line['base'] ?? 0),
                        'rate_percent' => $line['rate_percent'] ?? null,
                        'amount' => (float) $line['amount'],
                        'meta_json' => json_encode($line['meta'] ?? [], JSON_UNESCAPED_SLASHES),
                    ]);
                }

                foreach ($row['bonus_ids'] as $bonusId) {
                    $bonusLink->execute(['run_id' => $runId, 'id' => (int) $bonusId]);
                }

                $this->recordCalculationAudit($runId, $itemId, (int) $row['employee_id'], 'generated', $row, $userId);
            }

            $this->db->prepare('UPDATE payroll_runs SET total_gross = :gross, total_deductions = :deductions, total_net = :net WHERE id = :id')
                ->execute([
                    'gross' => $calculation['gross'],
                    'deductions' => $calculation['deductions'],
                    'net' => $calculation['net'],
                    'id' => $runId,
                ]);

            $this->recordCalculationAudit($runId, null, null, 'run_generated', [
                'gross_pay' => $calculation['gross'],
                'total_deductions' => $calculation['deductions'],
                'net_pay' => $calculation['net'],
                'employee_count' => $calculation['employees'],
            ], $userId);

            $this->db->commit();

            return [
                'gross' => $calculation['gross'],
                'deductions' => $calculation['deductions'],
                'net' => $calculation['net'],
                'employees' => $calculation['employees'],
            ];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function previewPayrollItems(int $runId): array
    {
        $run = $this->find($runId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        return $this->calculatePayrollItems($run);
    }

    private function calculatePayrollItems(array $run): array
    {
        $period = substr((string) ($run['pay_period'] ?? ''), 0, 7);
        if (!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', $period)) {
            throw new RuntimeException('Payroll period is invalid. Expected YYYY-MM.');
        }

        $periodStart = $period . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));
        $salaryAsOfDate = $periodEnd;
        $prorationMode = (string) ($run['proration_mode'] ?? 'Full Month');
        $employeeSalaryModel = new EmployeeSalary();
        $employeeDeductionModel = new EmployeeDeduction();

        $cid = Tenant::id();
        $cidFilter = $cid > 0 ? ' AND company_id = :cid' : '';
        $employeeStmt = $this->db->prepare(
            "SELECT id, full_name, employee_number, hired_at, termination_date
             FROM employees
             WHERE (hired_at IS NULL OR hired_at <= :period_end)
               AND (termination_date IS NULL OR termination_date >= :period_start)
               {$cidFilter}
             ORDER BY full_name ASC"
        );
        $employeeParams = ['period_start' => $periodStart, 'period_end' => $periodEnd];
        if ($cid > 0) { $employeeParams['cid'] = $cid; }
        $employeeStmt->execute($employeeParams);
        $employees = $employeeStmt->fetchAll();

        $bonusTenantFilter = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $bonusRunId = (int) ($run['id'] ?? 0);
        $existingBonusesStmt = $this->db->prepare(
            'SELECT bo.*, e.full_name AS employee_name, e.employee_number
             FROM bonuses_overtime bo
             JOIN employees e ON e.id = bo.employee_id
             WHERE (bo.payroll_run_id IS NULL OR bo.payroll_run_id = :run_id)' . $bonusTenantFilter . '
             ORDER BY bo.id ASC'
        );
        $bonusParams = ['run_id' => $bonusRunId];
        if ($cid > 0) { $bonusParams['cid'] = $cid; }
        $existingBonusesStmt->execute($bonusParams);
        $bonuses = $existingBonusesStmt->fetchAll();

        $bonusesByEmployee = [];
        foreach ($bonuses as $bonus) {
            $employeeId = (int) $bonus['employee_id'];
            $bonusesByEmployee[$employeeId][] = $bonus;
        }

        $items = [];
        $totals = [
            'gross' => 0.0,
            'deductions' => 0.0,
            'net' => 0.0,
            'employees' => 0,
            'employer_contributions' => 0.0,
        ];

        foreach ($employees as $employee) {
            $employeeId = (int) $employee['id'];
            $salary = $employeeSalaryModel->activeWithStructureForDate($employeeId, $salaryAsOfDate);

            if (!$salary) {
                continue;
            }

            $proration = $this->employmentProration($employee, $periodStart, $periodEnd, $prorationMode);
            $factor = (float) $proration['factor'];
            $basicPay = round((float) ($salary['basic_pay'] ?? 0) * $factor, 2);
            $housingAllowance = round((float) ($salary['housing_allowance'] ?? 0) * $factor, 2);
            $transportAllowance = round((float) ($salary['transport_allowance'] ?? 0) * $factor, 2);
            $otherAllowances = round((float) ($salary['other_allowances'] ?? 0) * $factor, 2);
            $bonusAmount = 0.0;
            $bonusIds = [];

            foreach ($bonusesByEmployee[$employeeId] ?? [] as $bonus) {
                $bonusAmount += (float) ($bonus['amount'] ?? 0);
                $bonusIds[] = (int) $bonus['id'];
            }

            $grossPay = $basicPay + $housingAllowance + $transportAllowance + $otherAllowances + $bonusAmount;
            $deductionLines = [];
            $employeeSpecificTotal = 0.0;

            foreach ($employeeDeductionModel->applicableForEmployeeAtDate($employeeId, $periodEnd) as $deduction) {
                if (EmployeeDeduction::isManagedStatutoryCode($deduction['deduction_code'] ?? null)) {
                    continue;
                }

                $calculationType = (string) ($deduction['calculation_type'] ?? 'Fixed');
                $rateOrAmount = (float) ($deduction['amount'] ?? $deduction['default_value'] ?? 0);
                $amount = $calculationType === 'Percent' ? round($grossPay * ($rateOrAmount / 100), 2) : round($rateOrAmount, 2);
                $employeeSpecificTotal += $amount;
                $deductionLines[] = [
                    'code' => (string) ($deduction['deduction_code'] ?? 'DED'),
                    'name' => (string) ($deduction['deduction_name'] ?? 'Deduction'),
                    'category' => 'employee_specific',
                    'calculation_type' => $calculationType,
                    'base' => $grossPay,
                    'rate_percent' => $calculationType === 'Percent' ? $rateOrAmount : null,
                    'amount' => $amount,
                ];
            }

            $statutoryTotal = 0.0;
            foreach (TaxCalculator::compute($grossPay, $basicPay) as $statutory) {
                $amount = round((float) $statutory['amount'], 2);
                $statutoryTotal += $amount;
                $deductionLines[] = [
                    'code' => (string) $statutory['code'],
                    'name' => (string) $statutory['label'],
                    'category' => 'statutory_employee',
                    'calculation_type' => 'Calculated',
                    'base' => (string) $statutory['code'] === 'NAPSA' ? $basicPay : $grossPay,
                    'rate_percent' => null,
                    'amount' => $amount,
                ];
            }

            $advanceDeduction = $this->calculateAdvanceDeduction($employeeId);
            if ($advanceDeduction > 0) {
                $deductionLines[] = [
                    'code' => 'ADV',
                    'name' => 'Salary Advance Recovery',
                    'category' => 'salary_advance',
                    'calculation_type' => 'Fixed',
                    'base' => $advanceDeduction,
                    'rate_percent' => null,
                    'amount' => $advanceDeduction,
                ];
            }

            foreach (TaxCalculator::employerContributions($grossPay, $basicPay) as $employer) {
                $totals['employer_contributions'] += (float) $employer['amount'];
                $deductionLines[] = [
                    'code' => (string) $employer['code'],
                    'name' => (string) $employer['label'],
                    'category' => 'statutory_employer',
                    'calculation_type' => 'Calculated',
                    'base' => (float) ($employer['base'] ?? 0),
                    'rate_percent' => (float) ($employer['rate_percent'] ?? 0),
                    'amount' => (float) $employer['amount'],
                ];
            }

            $deductions = round($employeeSpecificTotal + $statutoryTotal + $advanceDeduction, 2);
            $netPay = max(0, round($grossPay - $deductions, 2));

            $items[] = [
                'employee_id' => $employeeId,
                'employee_name' => (string) ($employee['full_name'] ?? ''),
                'employee_number' => (string) ($employee['employee_number'] ?? ''),
                'gross_pay' => round($grossPay, 2),
                'total_deductions' => $deductions,
                'net_pay' => $netPay,
                'deduction_lines' => $deductionLines,
                'bonus_ids' => $bonusIds,
                'employment_period_start' => $periodStart,
                'employment_period_end' => $periodEnd,
                'eligible_days' => $proration['eligible_days'],
                'period_days' => $proration['period_days'],
                'proration_factor' => $factor,
                'proration_mode' => $prorationMode,
            ];

            $totals['gross'] += $grossPay;
            $totals['deductions'] += $deductions;
            $totals['net'] += $netPay;
            $totals['employees']++;
        }

        $totals['gross'] = round($totals['gross'], 2);
        $totals['deductions'] = round($totals['deductions'], 2);
        $totals['net'] = round($totals['net'], 2);
        $totals['employer_contributions'] = round($totals['employer_contributions'], 2);
        $totals['items'] = $items;

        return $totals;
    }

    private function employmentProration(array $employee, string $periodStart, string $periodEnd, string $mode): array
    {
        $periodStartTs = strtotime($periodStart);
        $periodEndTs = strtotime($periodEnd);
        $employmentStartTs = !empty($employee['hired_at']) ? max($periodStartTs, strtotime((string) $employee['hired_at'])) : $periodStartTs;
        $employmentEndTs = !empty($employee['termination_date']) ? min($periodEndTs, strtotime((string) $employee['termination_date'])) : $periodEndTs;

        $periodDays = (int) date('t', $periodStartTs);
        $eligibleDays = $employmentEndTs >= $employmentStartTs
            ? (int) floor(($employmentEndTs - $employmentStartTs) / 86400) + 1
            : 0;

        $factor = $mode === 'Calendar Days'
            ? round($eligibleDays / max(1, $periodDays), 8)
            : ($eligibleDays > 0 ? 1.0 : 0.0);

        return [
            'eligible_days' => $eligibleDays,
            'period_days' => $periodDays,
            'factor' => min(1.0, max(0.0, $factor)),
        ];
    }

    private function calculateAdvanceDeduction(int $employeeId): float
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND e.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "SELECT sa.id, sa.monthly_deduction, sa.outstanding_balance
             FROM salary_advances sa
             JOIN employees e ON e.id = sa.employee_id
             WHERE sa.employee_id = :eid AND sa.status = 'Active' AND sa.outstanding_balance > 0$and
             ORDER BY start_date ASC LIMIT 1"
        );
        $params = ['eid' => $employeeId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);
        $advance = $stmt->fetch();

        if (!$advance) {
            return 0.0;
        }

        return round(min((float) $advance['monthly_deduction'], (float) $advance['outstanding_balance']), 2);
    }

    private function refreshRunTotals(int $runId): void
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(gross_pay), 0) AS gross,
                    COALESCE(SUM(total_deductions), 0) AS deductions,
                    COALESCE(SUM(net_pay), 0) AS net
             FROM payroll_items
             WHERE payroll_run_id = :run_id'
        );
        $stmt->execute(['run_id' => $runId]);
        $totals = $stmt->fetch() ?: ['gross' => 0, 'deductions' => 0, 'net' => 0];

        $this->db->prepare('UPDATE payroll_runs SET total_gross = :gross, total_deductions = :deductions, total_net = :net WHERE id = :id')
            ->execute([
                'gross' => round((float) $totals['gross'], 2),
                'deductions' => round((float) $totals['deductions'], 2),
                'net' => round((float) $totals['net'], 2),
                'id' => $runId,
            ]);
    }

    private function ensureAdjustmentSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS payroll_run_adjustments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                payroll_run_id BIGINT UNSIGNED NOT NULL,
                payroll_item_id BIGINT UNSIGNED NOT NULL,
                employee_id BIGINT UNSIGNED NOT NULL,
                adjustment_type ENUM('Deduction','Earning') NOT NULL DEFAULT 'Deduction',
                label VARCHAR(150) NOT NULL,
                amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
                reason TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pra_company_run (company_id, payroll_run_id),
                INDEX idx_pra_item (payroll_item_id),
                INDEX idx_pra_employee (employee_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    private function recordCalculationAudit(int $runId, ?int $itemId, ?int $employeeId, string $action, array $snapshot, ?int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payroll_calculation_audit
                (company_id, payroll_run_id, payroll_item_id, employee_id, action, gross_pay, total_deductions, net_pay, snapshot_json, created_by)
             VALUES
                (:company_id, :payroll_run_id, :payroll_item_id, :employee_id, :action, :gross_pay, :total_deductions, :net_pay, :snapshot_json, :created_by)'
        );
        $stmt->execute([
            'company_id' => Tenant::id(),
            'payroll_run_id' => $runId,
            'payroll_item_id' => $itemId,
            'employee_id' => $employeeId,
            'action' => $action,
            'gross_pay' => (float) ($snapshot['gross_pay'] ?? 0),
            'total_deductions' => (float) ($snapshot['total_deductions'] ?? 0),
            'net_pay' => (float) ($snapshot['net_pay'] ?? 0),
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_SLASHES),
            'created_by' => $userId,
        ]);
    }

    public function calculationHistory(int $runId): array
    {
        if (!$this->tableExists('payroll_calculation_audit')) {
            return [];
        }

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pca.company_id = :cid' : '';
        $stmt = $this->db->prepare(
            'SELECT pca.*, e.full_name AS employee_name, e.employee_number, u.full_name AS created_by_name
             FROM payroll_calculation_audit pca
             LEFT JOIN employees e ON e.id = pca.employee_id
             LEFT JOIN users u ON u.id = pca.created_by
             WHERE pca.payroll_run_id = :run_id' . $and . '
             ORDER BY pca.id DESC'
        );
        $params = ['run_id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function backfillDeductionBreakdown(int $runId): int
    {
        $run = $this->find($runId);
        if (!$run) {
            return 0;
        }

        $preview = $this->calculatePayrollItems($run);
        $itemsByEmployee = [];
        foreach ($preview['items'] as $row) {
            $itemsByEmployee[(int) $row['employee_id']] = $row;
        }

        $existingItems = $this->itemsForRun($runId);
        $this->db->prepare('DELETE FROM payroll_item_deductions WHERE payroll_run_id = :run_id')->execute(['run_id' => $runId]);
        $insert = $this->db->prepare(
            'INSERT INTO payroll_item_deductions
                (company_id, payroll_run_id, payroll_item_id, employee_id, deduction_code, deduction_name, deduction_category, calculation_type, calculation_base, rate_percent, amount, meta_json)
             VALUES
                (:company_id, :payroll_run_id, :payroll_item_id, :employee_id, :deduction_code, :deduction_name, :deduction_category, :calculation_type, :calculation_base, :rate_percent, :amount, :meta_json)'
        );

        $count = 0;
        foreach ($existingItems as $item) {
            $employeeId = (int) ($item['employee_id'] ?? 0);
            $row = $itemsByEmployee[$employeeId] ?? null;
            if (!$row) {
                continue;
            }

            foreach ($row['deduction_lines'] as $line) {
                $insert->execute([
                    'company_id' => Tenant::id(),
                    'payroll_run_id' => $runId,
                    'payroll_item_id' => (int) $item['id'],
                    'employee_id' => $employeeId,
                    'deduction_code' => (string) $line['code'],
                    'deduction_name' => (string) $line['name'],
                    'deduction_category' => (string) $line['category'],
                    'calculation_type' => $line['calculation_type'] ?? null,
                    'calculation_base' => (float) ($line['base'] ?? 0),
                    'rate_percent' => $line['rate_percent'] ?? null,
                    'amount' => (float) $line['amount'],
                    'meta_json' => json_encode(['source' => 'backfill'], JSON_UNESCAPED_SLASHES),
                ]);
                $count++;
            }
        }

        return $count;
    }

    public function search(string $keyword): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare("SELECT * FROM payroll_runs WHERE (pay_period LIKE :keyword OR status LIKE :keyword)$and ORDER BY run_date DESC, id DESC");
        $params = ['keyword' => '%' . $keyword . '%'];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function lockRun(int $runId, int $userId): void
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = $this->db->prepare(
            "UPDATE payroll_runs
             SET is_locked = 1, locked_at = NOW(), locked_by = :locked_by, status = 'Posted'
             WHERE id = :id$and"
        );
        $params = ['locked_by' => $userId, 'id' => $runId];
        if ($cid > 0) { $params['cid'] = $cid; }
        $stmt->execute($params);

        $this->recordCalculationAudit($runId, null, null, 'locked', [], $userId);
    }

    public function releasePayslips(int $runId, int $userId): void
    {
        $run = $this->find($runId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }
        if ((int) ($run['is_locked'] ?? 0) !== 1) {
            throw new RuntimeException('Payroll must be posted and locked before payslips are released.');
        }
        $summary = (new PayrollRunPayment())->summaryForRun($runId);
        if ((float) ($summary['paid_total'] ?? 0) + 0.01 < (float) ($run['total_net'] ?? 0)) {
            throw new RuntimeException('Record full payroll payment before releasing payslips.');
        }

        $stmt = $this->db->prepare(
            'UPDATE payroll_runs
             SET payslips_released = 1, payslips_released_at = NOW(), payslips_released_by = :uid
             WHERE id = :id AND company_id = :cid'
        );
        $stmt->execute(['uid' => $userId, 'id' => $runId, 'cid' => Tenant::id()]);
        WorkflowEvent::record('payroll', 'PayrollRun', $runId, 'Paid', 'Payslips Released', 'payslips_release', 'Payslips released to employee portal.');
    }

    public function reverseRun(int $runId, string $reason, int $userId): int
    {
        $run = $this->find($runId);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        if ((int) ($run['is_locked'] ?? 0) !== 1) {
            throw new RuntimeException('Only locked payroll runs can be reversed.');
        }

        if (!empty($run['reversed_at'])) {
            throw new RuntimeException('This payroll run has already been reversed.');
        }

        $cid = Tenant::id();
        $this->db->beginTransaction();

        try {
            $update = $this->db->prepare(
                "UPDATE payroll_runs
                 SET reversed_at = NOW(), reversed_by = :reversed_by, reversal_reason = :reason
                 WHERE id = :id" . ($cid > 0 ? ' AND company_id = :cid' : '')
            );
            $params = ['reversed_by' => $userId, 'reason' => $reason, 'id' => $runId];
            if ($cid > 0) { $params['cid'] = $cid; }
            $update->execute($params);

            $insertCorrection = $this->db->prepare(
                'INSERT INTO payroll_runs
                    (company_id, pay_period, run_date, status, total_gross, total_deductions, total_net, created_by, correction_of_run_id, tax_year_id, proration_mode)
                 VALUES
                    (:company_id, :pay_period, :run_date, "Draft", 0, 0, 0, :created_by, :correction_of_run_id, :tax_year_id, :proration_mode)'
            );
            $insertCorrection->execute([
                'company_id' => (int) ($run['company_id'] ?? $cid),
                'pay_period' => $this->nextCorrectionPeriod((string) $run['pay_period']),
                'run_date' => date('Y-m-d'),
                'created_by' => $userId,
                'correction_of_run_id' => $runId,
                'tax_year_id' => $run['tax_year_id'] ?? null,
                'proration_mode' => $run['proration_mode'] ?? 'Full Month',
            ]);
            $correctionId = (int) $this->db->lastInsertId();

            $this->db->prepare(
                'INSERT INTO payroll_run_reversals (company_id, payroll_run_id, correction_run_id, reason, reversed_by, reversed_at)
                 VALUES (:company_id, :payroll_run_id, :correction_run_id, :reason, :reversed_by, NOW())'
            )->execute([
                'company_id' => $cid,
                'payroll_run_id' => $runId,
                'correction_run_id' => $correctionId,
                'reason' => $reason,
                'reversed_by' => $userId,
            ]);

            $this->recordCalculationAudit($runId, null, null, 'reversed', ['reason' => $reason, 'correction_run_id' => $correctionId], $userId);

            $this->db->commit();

            return $correctionId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $exception;
        }
    }

    public function activeTaxYearForDate(string $date): ?array
    {
        $cid = Tenant::id();
        $stmt = $this->db->prepare(
            'SELECT * FROM payroll_tax_years
             WHERE company_id = :cid AND is_active = 1 AND starts_on <= :date_from AND ends_on >= :date_to
             ORDER BY starts_on DESC LIMIT 1'
        );
        $stmt->execute(['cid' => $cid, 'date_from' => $date, 'date_to' => $date]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function taxYears(): array
    {
        if (!$this->tableExists('payroll_tax_years')) {
            return [];
        }

        $stmt = $this->db->prepare('SELECT * FROM payroll_tax_years WHERE company_id = :cid ORDER BY starts_on DESC');
        $stmt->execute(['cid' => Tenant::id()]);

        return $stmt->fetchAll();
    }

    public function reversalHistory(int $runId): array
    {
        if (!$this->tableExists('payroll_run_reversals')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT prr.*, u.full_name AS reversed_by_name, correction.pay_period AS correction_period
             FROM payroll_run_reversals prr
             LEFT JOIN users u ON u.id = prr.reversed_by
             LEFT JOIN payroll_runs correction ON correction.id = prr.correction_run_id
             WHERE prr.company_id = :cid AND prr.payroll_run_id = :run_id
             ORDER BY prr.id DESC'
        );
        $stmt->execute(['cid' => Tenant::id(), 'run_id' => $runId]);

        return $stmt->fetchAll();
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => $table]);
            $cache[$table] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            $cache[$table] = false;
        }

        return $cache[$table];
    }

    private function nextCorrectionPeriod(string $period): string
    {
        $base = substr($period . '-CORR', 0, 16);
        $candidate = $base;
        $i = 1;

        while ($this->periodExists($candidate)) {
            $suffix = '-C' . $i++;
            $candidate = substr($base, 0, 20 - strlen($suffix)) . $suffix;
        }

        return $candidate;
    }

    public function periodExists(string $period, ?int $excludeId = null): bool
    {
        $cid = Tenant::id();
        $sql = 'SELECT id FROM payroll_runs WHERE pay_period = :pay_period'
             . ($cid > 0 ? ' AND company_id = :cid' : '');
        $params = ['pay_period' => $period];
        if ($cid > 0) { $params['cid'] = $cid; }

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

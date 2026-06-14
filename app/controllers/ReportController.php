<?php

declare(strict_types=1);

/**
 * Reporting controller for payroll and statutory outputs.
 */

class ReportController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $db = db();
        $cid = Tenant::id();
        $cpr = $cid > 0 ? 'company_id = ' . $cid : '1=1';
        $prc = $cid > 0 ? 'pr.company_id = ' . $cid : '1=1';
        $summary = [
            'payroll_runs' => (int) $db->query("SELECT COUNT(*) FROM payroll_runs WHERE {$cpr}")->fetchColumn(),
            'generated_items' => (int) $db->query("SELECT COUNT(*) FROM payroll_items pi JOIN payroll_runs pr ON pr.id = pi.payroll_run_id WHERE {$prc}")->fetchColumn(),
            'employees' => (int) $db->query("SELECT COUNT(*) FROM employees WHERE {$cpr}")->fetchColumn(),
            'deduction_types' => (int) $db->query("SELECT COUNT(*) FROM deduction_types WHERE {$cpr}")->fetchColumn(),
            'expiring_contracts' => (int) $db->query("SELECT COUNT(*) FROM employee_contracts ec JOIN employees e ON e.id = ec.employee_id WHERE {$cpr} AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn(),
        ];

        $this->render('reports/index', [
            'title' => 'Payroll Reports',
            'summary' => $summary,
        ]);
    }

    public function payrollCostTrend(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $rows = $this->fetchRows(
            "SELECT DATE_FORMAT(pr.run_date, '%Y-%m') AS period,
                    COUNT(DISTINCT pr.id) AS payroll_runs,
                    COUNT(pi.id) AS employees_paid,
                    COALESCE(SUM(pi.gross_pay),0) AS gross_cost,
                    COALESCE(SUM(pi.total_deductions),0) AS deductions,
                    COALESCE(SUM(pi.net_pay),0) AS net_cost
             FROM payroll_runs pr
             LEFT JOIN payroll_items pi ON pi.payroll_run_id = pr.id
             WHERE pr.company_id = :cid AND pr.status <> 'Draft' AND pr.reversed_at IS NULL
             GROUP BY DATE_FORMAT(pr.run_date, '%Y-%m')
             ORDER BY period ASC"
        );

        $this->renderAnalyticsReport('Payroll Cost Trend', 'Monthly payroll cost movement across gross, deductions, and net pay.', 'payroll-cost-trend', ['Period', 'Runs', 'Employees Paid', 'Gross Cost', 'Deductions', 'Net Cost'], array_map(static fn(array $r): array => [
            $r['period'], (int)$r['payroll_runs'], (int)$r['employees_paid'], (float)$r['gross_cost'], (float)$r['deductions'], (float)$r['net_cost'],
        ], $rows), ['period', 'gross_cost', 'net_cost']);
    }

    public function payrollSummary(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $cid = Tenant::id();
        $where = $cid > 0 ? 'WHERE pr.company_id = :cid' : '';
        $sql = 'SELECT pr.id, pr.pay_period, pr.run_date, pr.status,
                       pr.total_gross, pr.total_deductions, pr.total_net,
                       COUNT(pi.id) AS employee_count
                FROM payroll_runs pr
                LEFT JOIN payroll_items pi ON pi.payroll_run_id = pr.id
                ' . $where . '
                GROUP BY pr.id, pr.pay_period, pr.run_date, pr.status, pr.total_gross, pr.total_deductions, pr.total_net
                ORDER BY pr.run_date DESC, pr.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        $runs = $stmt->fetchAll();
        if ($this->input('export') === 'csv') {
            $this->streamGenericCsv('payroll-summary-' . date('Ymd') . '.csv', ['Pay Period', 'Run Date', 'Employees', 'Status', 'Gross', 'Deductions', 'Net'], array_map(static fn(array $r): array => [
                $r['pay_period'] ?? '', $r['run_date'] ?? '', (int) ($r['employee_count'] ?? 0), $r['status'] ?? '',
                number_format((float) ($r['total_gross'] ?? 0), 2), number_format((float) ($r['total_deductions'] ?? 0), 2), number_format((float) ($r['total_net'] ?? 0), 2),
            ], $runs));
            return;
        }

        $this->render('reports/payroll-summary', [
            'title' => 'Payroll Summary Report',
            'runs' => $runs,
        ]);
    }

    public function departmentCost(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'Viewer']);

        $sql = 'SELECT COALESCE(d.name, "Unassigned") AS department_name,
                       COUNT(pi.id) AS item_count,
                       SUM(pi.gross_pay) AS total_gross,
                       SUM(pi.total_deductions) AS total_deductions,
                       SUM(pi.net_pay) AS total_net
                FROM payroll_items pi
                JOIN employees e ON e.id = pi.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.company_id = :cid
                GROUP BY COALESCE(d.name, "Unassigned")
                ORDER BY total_net DESC, department_name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute(['cid' => Tenant::id()]);
        $departments = $stmt->fetchAll();
        if ($this->input('export') === 'csv') {
            $this->streamGenericCsv('department-cost-' . date('Ymd') . '.csv', ['Department', 'Payroll Items', 'Gross', 'Deductions', 'Net'], array_map(static fn(array $r): array => [
                $r['department_name'] ?? '', (int) ($r['item_count'] ?? 0), number_format((float) ($r['total_gross'] ?? 0), 2), number_format((float) ($r['total_deductions'] ?? 0), 2), number_format((float) ($r['total_net'] ?? 0), 2),
            ], $departments));
            return;
        }

        $this->render('reports/department-cost', [
            'title' => 'Department Cost Report',
            'departments' => $departments,
        ]);
    }

    public function headcountTrend(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $rows = $this->fetchRows(
            "SELECT DATE_FORMAT(COALESCE(hired_at, created_at), '%Y-%m') AS period,
                    COUNT(*) AS joined,
                    SUM(CASE WHEN contract_status = 'Ended' OR archived_at IS NOT NULL THEN 1 ELSE 0 END) AS ended
             FROM employees
             WHERE company_id = :cid
             GROUP BY DATE_FORMAT(COALESCE(hired_at, created_at), '%Y-%m')
             ORDER BY period ASC"
        );

        $running = 0;
        $data = [];
        foreach ($rows as $row) {
            $joined = (int) ($row['joined'] ?? 0);
            $ended = (int) ($row['ended'] ?? 0);
            $running += $joined - $ended;
            $data[] = [$row['period'], $joined, $ended, max(0, $running)];
        }

        $this->renderAnalyticsReport('Headcount Trend', 'Monthly joiners, leavers, and estimated active headcount.', 'headcount-trend', ['Period', 'Joined', 'Ended', 'Estimated Headcount'], $data, ['period', 'joined', 'headcount']);
    }

    public function leaveLiability(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $year = (int) ($this->input('year', date('Y')) ?? date('Y'));
        $rows = $this->fetchRows(
            "SELECT e.employee_number, e.full_name, COALESCE(d.name,'Unassigned') AS department_name,
                    lt.name AS leave_type,
                    lb.entitled_days, lb.used_days,
                    GREATEST(lb.entitled_days - lb.used_days, 0) AS remaining_days,
                    COALESCE(ss.basic_pay, 0) AS monthly_basic,
                    GREATEST(lb.entitled_days - lb.used_days, 0) * (COALESCE(ss.basic_pay, 0) / 22) AS estimated_liability
             FROM leave_balances lb
             JOIN employees e ON e.id = lb.employee_id
             JOIN leave_types lt ON lt.id = lb.leave_type_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
             LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
             WHERE e.company_id = :cid AND lb.year = :year
             ORDER BY estimated_liability DESC, e.full_name ASC",
            ['year' => $year]
        );

        $this->renderAnalyticsReport('Leave Liability Report', 'Estimated leave-day liability using active monthly basic pay divided by 22 working days.', 'leave-liability', ['Employee #', 'Employee', 'Department', 'Leave Type', 'Entitled', 'Used', 'Remaining', 'Monthly Basic', 'Estimated Liability'], array_map(static fn(array $r): array => [
            $r['employee_number'], $r['full_name'], $r['department_name'], $r['leave_type'], (float)$r['entitled_days'], (float)$r['used_days'], (float)$r['remaining_days'], (float)$r['monthly_basic'], (float)$r['estimated_liability'],
        ], $rows));
    }

    public function statutory(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $period = trim((string) ($this->input('period') ?? ''));
        $runId = (int) ($this->input('run_id', 0) ?? 0);
        $statutoryCode = strtoupper(trim((string) ($this->input('statutory_code', 'NAPSA') ?? 'NAPSA')));
        $allowedCodes = ['NAPSA', 'PAYE', 'NHIMA'];
        if (!in_array($statutoryCode, $allowedCodes, true)) {
            $statutoryCode = 'NAPSA';
        }
        $params = ['cid' => Tenant::id()];
        $where = 'pid.company_id = :cid';
        $where .= ' AND UPPER(pid.deduction_code) = :statutory_code';
        $params['statutory_code'] = $statutoryCode;
        if ($runId > 0) {
            $where .= ' AND pr.id = :run_id';
            $params['run_id'] = $runId;
        }
        if ($period !== '') {
            $where .= ' AND pr.pay_period = :period';
            $params['period'] = $period;
        }

        $sql = "SELECT pid.deduction_name,
                       pid.deduction_code,
                       pid.deduction_category,
                       COUNT(DISTINCT pid.employee_id) AS employee_count,
                       SUM(pid.amount) AS total_amount
                FROM payroll_item_deductions pid
                JOIN payroll_runs pr ON pr.id = pid.payroll_run_id
                WHERE $where
                  AND pid.deduction_category IN ('statutory_employee','statutory_employer')
                  AND pr.reversed_at IS NULL
                GROUP BY pid.deduction_name, pid.deduction_code, pid.deduction_category
                ORDER BY pid.deduction_code ASC, pid.deduction_category ASC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $statutory = $stmt->fetchAll();

        $details = $this->statutoryEmployeeDetails($runId, $period, $statutoryCode);
        $runs = $this->statutoryPayrollRuns();
        $cards = $this->statutoryContributionCards($details);
        $payment = $runId > 0 ? (new StatutoryPayment())->forRunAndCode($runId, $statutoryCode) : null;
        $recentPayments = (new StatutoryPayment())->recent(10);

        if ($this->input('export') === 'csv') {
            $this->streamGenericCsv(strtolower($statutoryCode) . '-statutory-deductions-' . date('Ymd') . '.csv', ['Period', 'Employee #', 'Employee', 'NAPSA No.', 'TPIN', 'NRC', 'Deduction', 'Code', 'Category', 'Base', 'Amount'], array_map(static fn(array $r): array => [
                $r['pay_period'] ?? '', $r['employee_number'] ?? '', $r['full_name'] ?? '', $r['napsa_number'] ?? '', $r['tpin'] ?? '', $r['nrc_number'] ?? '',
                $r['deduction_name'] ?? '', $r['deduction_code'] ?? '', $r['deduction_category'] ?? '', number_format((float) ($r['calculation_base'] ?? 0), 2), number_format((float) ($r['amount'] ?? 0), 2),
            ], $details));
            return;
        }

        if ($this->input('export') === 'filing') {
            $this->streamStatutoryFilingCsv($statutoryCode, $details);
            return;
        }

        $this->render('reports/statutory', [
            'title'    => 'Statutory Report',
            'statutory' => $statutory,
            'details' => $details,
            'runs' => $runs,
            'cards' => $cards,
            'runId' => $runId,
            'period' => $period,
            'statutoryCode' => $statutoryCode,
            'statutoryOptions' => $allowedCodes,
            'payment' => $payment,
            'recentPayments' => $recentPayments,
            'csrf' => Session::csrfToken(),
        ]);
    }

    public function recordStatutoryPayment(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('report/statutory');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('report/statutory');
        }

        $runId = (int) $this->input('run_id', 0);
        $code = strtoupper(trim((string) $this->input('statutory_code', '')));
        if ($runId <= 0 || !in_array($code, ['PAYE', 'NAPSA', 'NHIMA'], true)) {
            Session::flash('error', 'Select a payroll run and statutory item before recording payment.');
            redirect('report/statutory');
        }

        $run = (new PayrollRun())->find($runId);
        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('report/statutory');
        }

        $cards = $this->statutoryContributionCards($this->statutoryEmployeeDetails($runId, '', $code));
        $status = (string) $this->input('status', 'Pending');
        if (!in_array($status, ['Pending','Paid','Partially Paid','Overdue','Cancelled'], true)) {
            $status = 'Pending';
        }

        (new StatutoryPayment())->upsertForRun([
            'payroll_run_id' => $runId,
            'pay_period' => (string) $run['pay_period'],
            'statutory_code' => $code,
            'employee_amount' => (float) $cards['employee_total'],
            'employer_amount' => (float) $cards['employer_total'],
            'total_amount' => (float) $cards['combined_total'],
            'payment_reference' => trim((string) $this->input('payment_reference', '')),
            'payment_date' => trim((string) $this->input('payment_date', '')) ?: null,
            'status' => $status,
            'notes' => trim((string) $this->input('notes', '')),
            'created_by' => (int) (current_user()['id'] ?? 0),
        ]);

        AuditLog::record('statutory_payment_record', "Recorded {$code} statutory payment for payroll run #{$runId}.", 'StatutoryPayment');
        Session::flash('success', "{$code} payment tracking updated.");
        redirect('report/statutory?run_id=' . $runId . '&statutory_code=' . urlencode($code));
    }

    public function napsaReturn(string $id = ''): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $runId = (int) ($id !== '' ? $id : ($this->input('run_id', 0) ?? 0));
        if ($runId <= 0) {
            Session::flash('error', 'Please select a payroll run before downloading the NAPSA return.');
            redirect('report/statutory');
        }

        $path = (new NapsaReturnExporter())->exportForPayrollRun($runId);
        $filename = basename($path);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.ms-excel.sheet.macroEnabled.12');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        readfile($path);
        exit;
    }

    public function gratuityLiability(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $settings = new Setting();
        $rate = max(0.0, (float) $settings->numericValue('gratuity_rate_percent', 5.0));
        $qualifyingYears = max(0.0, (float) $settings->numericValue('gratuity_qualifying_years', 2.0));

        $rows = $this->fetchRows(
            "SELECT e.employee_number, e.full_name, COALESCE(d.name,'Unassigned') AS department_name,
                    ec.contract_number, ec.start_date, ec.end_date,
                    TIMESTAMPDIFF(MONTH, ec.start_date, COALESCE(NULLIF(ec.end_date, '0000-00-00'), CURDATE())) / 12 AS contract_years,
                    COALESCE(ss.basic_pay, 0) AS monthly_basic
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
             LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
             WHERE e.company_id = :cid AND ec.status IN ('Active','Renewed') AND ec.approval_status = 'Approved'
             ORDER BY e.full_name ASC"
        );

        $data = [];
        foreach ($rows as $row) {
            $years = max(0.0, (float) ($row['contract_years'] ?? 0));
            $annualBasic = (float) ($row['monthly_basic'] ?? 0) * 12;
            $accrued = $annualBasic * ($rate / 100) * $years;
            $qualifies = $years >= $qualifyingYears ? 'Yes' : 'No';
            $data[] = [$row['employee_number'], $row['full_name'], $row['department_name'], $row['contract_number'], $row['start_date'], $row['end_date'] ?: 'Open-ended', round($years, 2), (float)$row['monthly_basic'], $rate . '%', $qualifies, $accrued];
        }

        $this->renderAnalyticsReport('Gratuity Liability Report', 'Accrued gratuity based on company policy and active approved contracts.', 'gratuity-liability', ['Employee #', 'Employee', 'Department', 'Contract #', 'Start Date', 'End Date', 'Years Served', 'Monthly Basic', 'Rate', 'Qualified', 'Accrued Gratuity'], $data);
    }

    public function employeeTurnover(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $rows = $this->fetchRows(
            "SELECT DATE_FORMAT(COALESCE(e.termination_date, e.archived_at, e.updated_at), '%Y-%m') AS period,
                    COALESCE(d.name,'Unassigned') AS department_name,
                    COUNT(*) AS leavers
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE e.company_id = :cid
               AND (e.contract_status = 'Ended' OR e.archived_at IS NOT NULL OR e.termination_date IS NOT NULL)
             GROUP BY DATE_FORMAT(COALESCE(e.termination_date, e.archived_at, e.updated_at), '%Y-%m'), COALESCE(d.name,'Unassigned')
             ORDER BY period DESC, department_name ASC"
        );

        $this->renderAnalyticsReport('Employee Turnover Report', 'Employees ended, terminated, or archived by month and department.', 'employee-turnover', ['Period', 'Department', 'Leavers'], array_map(static fn(array $r): array => [
            $r['period'], $r['department_name'], (int)$r['leavers'],
        ], $rows));
    }

    public function salaryVariance(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $rows = $this->fetchRows(
            "SELECT e.employee_number, e.full_name,
                    MAX(CASE WHEN ranked.rn = 1 THEN ranked.pay_period END) AS current_period,
                    MAX(CASE WHEN ranked.rn = 1 THEN ranked.gross_pay END) AS current_gross,
                    MAX(CASE WHEN ranked.rn = 1 THEN ranked.net_pay END) AS current_net,
                    MAX(CASE WHEN ranked.rn = 2 THEN ranked.pay_period END) AS previous_period,
                    MAX(CASE WHEN ranked.rn = 2 THEN ranked.gross_pay END) AS previous_gross,
                    MAX(CASE WHEN ranked.rn = 2 THEN ranked.net_pay END) AS previous_net
             FROM (
                SELECT pi.employee_id, pr.pay_period, pi.gross_pay, pi.net_pay,
                       ROW_NUMBER() OVER (PARTITION BY pi.employee_id ORDER BY pr.run_date DESC, pr.id DESC) AS rn
                FROM payroll_items pi
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                WHERE pr.company_id = :cid AND pr.status <> 'Draft' AND pr.reversed_at IS NULL
             ) ranked
             JOIN employees e ON e.id = ranked.employee_id
             WHERE ranked.rn <= 2
             GROUP BY e.id, e.employee_number, e.full_name
             ORDER BY e.full_name ASC"
        );

        $data = [];
        foreach ($rows as $row) {
            $currentGross = (float) ($row['current_gross'] ?? 0);
            $previousGross = (float) ($row['previous_gross'] ?? 0);
            $currentNet = (float) ($row['current_net'] ?? 0);
            $previousNet = (float) ($row['previous_net'] ?? 0);
            $data[] = [$row['employee_number'], $row['full_name'], $row['current_period'], $currentGross, $row['previous_period'] ?? '', $previousGross, $currentGross - $previousGross, $currentNet - $previousNet];
        }

        $this->renderAnalyticsReport('Salary Variance Report', 'Compares each employee’s latest payroll item with their previous payroll item.', 'salary-variance', ['Employee #', 'Employee', 'Current Period', 'Current Gross', 'Previous Period', 'Previous Gross', 'Gross Variance', 'Net Variance'], $data);
    }

    public function ytdSummary(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $year  = (int) ($this->input('year', date('Y')) ?? date('Y'));
        $cid = Tenant::id();
        $yearStmt = db()->prepare('SELECT DISTINCT YEAR(run_date) AS y FROM payroll_runs WHERE company_id = :cid ORDER BY y DESC');
        $yearStmt->execute(['cid' => $cid]);
        $years = $yearStmt->fetchAll(\PDO::FETCH_COLUMN);

        $sql = 'SELECT e.full_name AS employee_name,
                       e.employee_number,
                       MONTH(pr.run_date) AS month_num,
                       SUM(pi.gross_pay)        AS gross,
                       SUM(pi.total_deductions) AS deductions,
                       SUM(pi.net_pay)          AS net
                FROM payroll_items pi
                JOIN employees e     ON e.id  = pi.employee_id
                JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                WHERE YEAR(pr.run_date) = :yr
                  AND pr.company_id = :cid
                  AND pr.status NOT IN (\'Draft\')
                GROUP BY e.id, e.full_name, e.employee_number, MONTH(pr.run_date)
                ORDER BY e.full_name ASC, month_num ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute(['yr' => $year, 'cid' => $cid]);
        $rows = $stmt->fetchAll();

        if ($this->input('export') === 'csv') {
            $this->streamYtdCsv($rows, $year);
            return;
        }

        $byEmployee = [];
        foreach ($rows as $r) {
            $key = $r['employee_number'];
            if (!isset($byEmployee[$key])) {
                $byEmployee[$key] = ['name' => $r['employee_name'], 'number' => $r['employee_number'], 'months' => []];
            }
            $byEmployee[$key]['months'][(int) $r['month_num']] = $r;
        }

        $this->render('reports/ytd-summary', [
            'title'      => 'YTD Payroll Summary ' . $year,
            'year'       => $year,
            'years'      => $years,
            'byEmployee' => $byEmployee,
        ]);
    }

    public function contractExpiry(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $dateFrom = trim((string) ($this->input('date_from') ?? ''));
        $dateTo   = trim((string) ($this->input('date_to')   ?? ''));

        if ($dateFrom === '') { $dateFrom = date('Y-m-d'); }
        if ($dateTo   === '') { $dateTo   = date('Y-m-d', strtotime('+3 months')); }

        $sql = 'SELECT ec.*, e.full_name AS employee_name, e.employee_number, e.designation,
                       DATEDIFF(ec.end_date, CURDATE()) AS days_remaining
                FROM employee_contracts ec
                JOIN employees e ON e.id = ec.employee_id
                WHERE ec.end_date BETWEEN :df AND :dt
                  AND e.company_id = :cid
                ORDER BY ec.end_date ASC, e.full_name ASC';

        $stmt = db()->prepare($sql);
        $stmt->execute(['df' => $dateFrom, 'dt' => $dateTo, 'cid' => Tenant::id()]);
        $contracts = $stmt->fetchAll();

        if ($this->input('export') === 'csv') {
            $this->streamContractCsv($contracts, $dateFrom, $dateTo);
            return;
        }

        $this->render('reports/contract-expiry', [
            'title'     => 'Contract Expiry Report',
            'contracts' => $contracts,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    public function leaveUtilization(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $db     = db();
        $year   = (int) ($this->input('year',   date('Y')) ?? date('Y'));
        $deptId = (int) ($this->input('dept_id', 0)        ?? 0);

        $cid = Tenant::id();
        $leaveStmt = $db->prepare("SELECT id, name, code, days_per_year FROM leave_types WHERE is_active=1 AND company_id = :cid ORDER BY name ASC");
        $leaveStmt->execute(['cid' => $cid]);
        $leaveTypes = $leaveStmt->fetchAll();
        $deptStmt = $db->prepare("SELECT id, name FROM departments WHERE company_id = :cid ORDER BY name ASC");
        $deptStmt->execute(['cid' => $cid]);
        $departments = $deptStmt->fetchAll();
        $years       = $db->query("SELECT DISTINCT YEAR(start_date) AS y FROM leave_requests ORDER BY y DESC")->fetchAll(\PDO::FETCH_COLUMN);
        if (!in_array((string) $year, array_map('strval', $years), true)) { array_unshift($years, $year); }

        $empSql = "SELECT e.id, e.full_name AS employee_name, e.employee_number,
                          COALESCE(d.name,'Unassigned') AS department_name
                   FROM employees e
                   LEFT JOIN departments d ON d.id = e.department_id
                   WHERE e.contract_status = 'Active'"
                 . ' AND e.company_id = :cid'
                 . ($deptId > 0 ? ' AND e.department_id = :did' : '')
                 . " ORDER BY e.full_name ASC";

        $empStmt = $db->prepare($empSql);
        $empParams = ['cid' => $cid];
        if ($deptId > 0) { $empParams['did'] = $deptId; }
        $empStmt->execute($empParams);
        $employees = $empStmt->fetchAll();

        $balStmt = $db->prepare(
            "SELECT employee_id, leave_type_id, entitled_days, used_days
             FROM leave_balances lb
             JOIN employees e ON e.id = lb.employee_id
             WHERE lb.year = :yr AND e.company_id = :cid"
        );
        $balStmt->execute(['yr' => $year, 'cid' => $cid]);
        $balMap = [];
        foreach ($balStmt->fetchAll() as $b) {
            $balMap[(int)$b['employee_id']][(int)$b['leave_type_id']] = $b;
        }

        $pendStmt = $db->prepare(
            "SELECT employee_id, leave_type_id, SUM(total_days) AS pending_days
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             WHERE lr.status = 'Pending' AND YEAR(lr.start_date) = :yr AND e.company_id = :cid
             GROUP BY lr.employee_id, lr.leave_type_id"
        );
        $pendStmt->execute(['yr' => $year, 'cid' => $cid]);
        $pendMap = [];
        foreach ($pendStmt->fetchAll() as $p) {
            $pendMap[(int)$p['employee_id']][(int)$p['leave_type_id']] = (float)$p['pending_days'];
        }

        $ltDefaults = [];
        foreach ($leaveTypes as $lt) { $ltDefaults[(int)$lt['id']] = (float)$lt['days_per_year']; }

        $matrix = [];
        foreach ($employees as $emp) {
            $eid  = (int)$emp['id'];
            $row  = ['employee_name' => $emp['employee_name'], 'employee_number' => $emp['employee_number'], 'department_name' => $emp['department_name'], 'types' => []];
            foreach ($leaveTypes as $lt) {
                $tid = (int)$lt['id'];
                $bal = $balMap[$eid][$tid] ?? null;
                $ent = $bal ? (float)$bal['entitled_days'] : $ltDefaults[$tid];
                $usd = $bal ? (float)$bal['used_days']     : 0.0;
                $rem = $ent - $usd;
                $pnd = $pendMap[$eid][$tid] ?? 0.0;
                $row['types'][$tid] = ['entitled' => $ent, 'used' => $usd, 'remaining' => $rem, 'pending' => $pnd];
            }
            $matrix[] = $row;
        }

        if ($this->input('export') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="leave-utilization-' . $year . '.csv"');
            $out = fopen('php://output', 'w');
            $header = ['Employee', 'Employee #', 'Department'];
            foreach ($leaveTypes as $lt) { $header[] = $lt['name'].' Entitled'; $header[] = $lt['name'].' Used'; $header[] = $lt['name'].' Remaining'; $header[] = $lt['name'].' Pending'; }
            fputcsv($out, $header);
            foreach ($matrix as $row) {
                $line = [$row['employee_name'], $row['employee_number'], $row['department_name']];
                foreach ($leaveTypes as $lt) {
                    $t = $row['types'][(int)$lt['id']];
                    $line[] = $t['entitled']; $line[] = $t['used']; $line[] = $t['remaining']; $line[] = $t['pending'];
                }
                fputcsv($out, $line);
            }
            fclose($out);
            exit;
        }

        $this->render('reports/leave-utilization', [
            'title'       => 'Leave Utilization ' . $year,
            'matrix'      => $matrix,
            'leaveTypes'  => $leaveTypes,
            'departments' => $departments,
            'year'        => $year,
            'years'       => $years,
            'deptId'      => $deptId,
        ]);
    }

    public function salaryAdvanceReport(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $status = trim((string) ($this->input('status', '') ?? ''));
        $params = [];
        $where  = 'WHERE e.company_id = :cid';
        $params = ['cid' => Tenant::id()];
        if ($status !== '') {
            $where  .= ' AND sa.status = :status';
            $params['status'] = $status;
        }

        $sql = "SELECT sa.*, e.full_name AS employee_name, e.employee_number,
                       COALESCE(d.name,'Unassigned') AS department_name,
                       u.full_name AS approved_by_name
                FROM salary_advances sa
                JOIN employees e ON e.id = sa.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN users u ON u.id = sa.approved_by
                {$where}
                ORDER BY sa.created_at DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if ($this->input('export') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="salary-advances.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Employee #', 'Department', 'Amount', 'Monthly Deduction', 'Outstanding', 'Start Date', 'Status', 'Approved By', 'Requested On']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['employee_name'], $r['employee_number'], $r['department_name'],
                    number_format((float)$r['amount'],2), number_format((float)$r['monthly_deduction'],2),
                    number_format((float)$r['outstanding_balance'],2), $r['start_date'], $r['status'],
                    $r['approved_by_name'] ?? '—', date('d M Y', strtotime((string)$r['created_at']))]);
            }
            fclose($out);
            exit;
        }

        $this->render('reports/salary-advance-report', [
            'title'   => 'Salary Advance Report',
            'rows'    => $rows,
            'status'  => $status,
        ]);
    }

    public function employeeCompletion(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Viewer']);

        $cid = Tenant::id();
        $stmt = db()->prepare(
            "SELECT e.*, d.name AS department_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE e.company_id = :cid
             ORDER BY e.full_name ASC"
        );
        $stmt->execute(['cid' => $cid]);
        $employees = $stmt->fetchAll();

        $docStmt = db()->prepare(
            "SELECT ed.employee_id, ed.document_type
             FROM employee_documents ed
             JOIN employees e ON e.id = ed.employee_id
             WHERE e.company_id = :cid"
        );
        $docStmt->execute(['cid' => $cid]);
        $docs = [];
        foreach ($docStmt->fetchAll() as $doc) {
            $docs[(int) $doc['employee_id']][] = (string) $doc['document_type'];
        }

        $requiredDocs = ['NRC / National ID', 'Bank Statement'];
        $rows = [];
        foreach ($employees as $employee) {
            $missing = $this->missingEmployeeCompletionItems($employee, $docs[(int) $employee['id']] ?? [], $requiredDocs);
            $total = 10 + count($requiredDocs);
            $percent = (int) round((($total - count($missing)) / $total) * 100);
            $rows[] = [
                'employee' => $employee,
                'percent' => $percent,
                'missing' => $missing,
            ];
        }

        if ($this->input('export') === 'csv') {
            $this->streamGenericCsv('employee-completion-' . date('Ymd') . '.csv', ['Employee', 'Employee #', 'Department', 'Completion %', 'Missing Items'], array_map(static fn(array $row): array => [
                $row['employee']['full_name'] ?? '',
                $row['employee']['employee_number'] ?? '',
                $row['employee']['department_name'] ?? '',
                (string) $row['percent'],
                implode('; ', $row['missing']),
            ], $rows));
            return;
        }

        $this->render('reports/employee-completion', [
            'title' => 'Employee Completion Report',
            'rows' => $rows,
        ]);
    }

    private function fetchRows(string $sql, array $params = []): array
    {
        $stmt = db()->prepare($sql);
        $stmt->execute(['cid' => Tenant::id()] + $params);
        return $stmt->fetchAll();
    }

    private function statutoryPayrollRuns(): array
    {
        $cid = Tenant::id();
        $sql = 'SELECT pr.id, pr.pay_period, pr.run_date, pr.status, COUNT(pi.id) AS employee_count
                FROM payroll_runs pr
                JOIN payroll_items pi ON pi.payroll_run_id = pr.id
                WHERE pr.reversed_at IS NULL' . ($cid > 0 ? ' AND pr.company_id = :cid' : '') . '
                GROUP BY pr.id, pr.pay_period, pr.run_date, pr.status
                ORDER BY pr.run_date DESC, pr.id DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        return $stmt->fetchAll();
    }

    private function statutoryEmployeeDetails(int $runId = 0, string $period = '', string $statutoryCode = 'NAPSA'): array
    {
        $cid = Tenant::id();
        $params = [];
        $where = 'pr.reversed_at IS NULL';
        if ($cid > 0) {
            $where .= ' AND pr.company_id = :run_cid AND e.company_id = :employee_cid';
            $params['run_cid'] = $cid;
            $params['employee_cid'] = $cid;
        }
        if ($runId > 0) {
            $where .= ' AND pr.id = :run_id';
            $params['run_id'] = $runId;
        }
        if ($period !== '') {
            $where .= ' AND pr.pay_period = :period';
            $params['period'] = $period;
        }
        $where .= ' AND UPPER(pid.deduction_code) = :statutory_code';
        $params['statutory_code'] = strtoupper($statutoryCode);

        $sql = "SELECT pr.id AS payroll_run_id, pr.pay_period, pr.run_date,
                       e.employee_number, e.full_name, e.napsa_number, e.tpin, e.nrc_number,
                       pid.deduction_name, pid.deduction_code, pid.deduction_category,
                       pid.calculation_base, pid.rate_percent, pid.amount
                FROM payroll_item_deductions pid
                JOIN payroll_items pi ON pi.id = pid.payroll_item_id
                JOIN payroll_runs pr ON pr.id = pid.payroll_run_id
                JOIN employees e ON e.id = pid.employee_id
                WHERE {$where}
                  AND pid.deduction_category IN ('statutory_employee','statutory_employer')
                ORDER BY pr.run_date DESC, e.full_name ASC, pid.deduction_code ASC, pid.deduction_category ASC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function statutoryContributionCards(array $details): array
    {
        $employee = 0.0;
        $employer = 0.0;
        $employees = [];

        foreach ($details as $row) {
            $category = (string) ($row['deduction_category'] ?? '');
            $amount = (float) ($row['amount'] ?? 0);
            if ($category === 'statutory_employer') {
                $employer += $amount;
            } else {
                $employee += $amount;
            }

            $employeeKey = (string) ($row['employee_number'] ?? $row['full_name'] ?? '');
            if ($employeeKey !== '') {
                $employees[$employeeKey] = true;
            }
        }

        return [
            'employee_total' => round($employee, 2),
            'employer_total' => round($employer, 2),
            'combined_total' => round($employee + $employer, 2),
            'employee_count' => count($employees),
        ];
    }

    private function streamStatutoryFilingCsv(string $code, array $details): void
    {
        $code = strtoupper($code);
        $rowsByEmployee = [];
        foreach ($details as $row) {
            $key = (string) ($row['payroll_run_id'] ?? '') . ':' . (string) ($row['employee_number'] ?? $row['full_name'] ?? '');
            if (!isset($rowsByEmployee[$key])) {
                $rowsByEmployee[$key] = $row + ['employee_amount' => 0.0, 'employer_amount' => 0.0];
            }
            if ((string) ($row['deduction_category'] ?? '') === 'statutory_employer') {
                $rowsByEmployee[$key]['employer_amount'] += (float) ($row['amount'] ?? 0);
            } else {
                $rowsByEmployee[$key]['employee_amount'] += (float) ($row['amount'] ?? 0);
            }
        }

        $filename = strtolower($code) . '-filing-' . date('Ymd') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');

        if ($code === 'PAYE') {
            fputcsv($out, ['Period', 'TPIN', 'NRC', 'Employee No.', 'Employee Name', 'Taxable Pay', 'PAYE Amount']);
            foreach ($rowsByEmployee as $row) {
                fputcsv($out, [
                    $row['pay_period'] ?? '',
                    $row['tpin'] ?? '',
                    $row['nrc_number'] ?? '',
                    $row['employee_number'] ?? '',
                    $row['full_name'] ?? '',
                    number_format((float) ($row['calculation_base'] ?? 0), 2, '.', ''),
                    number_format((float) ($row['employee_amount'] ?? 0), 2, '.', ''),
                ]);
            }
        } elseif ($code === 'NHIMA') {
            fputcsv($out, ['Period', 'NRC', 'Employee No.', 'Employee Name', 'Gross Wage', 'Employee Share', 'Employer Share', 'Total']);
            foreach ($rowsByEmployee as $row) {
                $employee = (float) ($row['employee_amount'] ?? 0);
                $employer = (float) ($row['employer_amount'] ?? 0);
                fputcsv($out, [
                    $row['pay_period'] ?? '',
                    $row['nrc_number'] ?? '',
                    $row['employee_number'] ?? '',
                    $row['full_name'] ?? '',
                    number_format((float) ($row['calculation_base'] ?? 0), 2, '.', ''),
                    number_format($employee, 2, '.', ''),
                    number_format($employer, 2, '.', ''),
                    number_format($employee + $employer, 2, '.', ''),
                ]);
            }
        } else {
            fputcsv($out, ['Period', 'NAPSA No.', 'NRC', 'Employee No.', 'Employee Name', 'Gross Wage', 'Employee Share', 'Employer Share', 'Total']);
            foreach ($rowsByEmployee as $row) {
                $employee = (float) ($row['employee_amount'] ?? 0);
                $employer = (float) ($row['employer_amount'] ?? 0);
                fputcsv($out, [
                    $row['pay_period'] ?? '',
                    $row['napsa_number'] ?? '',
                    $row['nrc_number'] ?? '',
                    $row['employee_number'] ?? '',
                    $row['full_name'] ?? '',
                    number_format((float) ($row['calculation_base'] ?? 0), 2, '.', ''),
                    number_format($employee, 2, '.', ''),
                    number_format($employer, 2, '.', ''),
                    number_format($employee + $employer, 2, '.', ''),
                ]);
            }
        }

        AuditLog::record('statutory_filing_export', "Exported {$code} filing CSV.", 'Report');
        fclose($out);
        exit;
    }

    private function renderAnalyticsReport(string $title, string $description, string $slug, array $headers, array $rows, array $chartKeys = []): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $method = (string) ($trace[1]['function'] ?? $slug);
        $export = strtolower((string) ($this->input('export', '') ?? ''));
        if ($export === 'csv' || $export === 'xls') {
            $this->streamReportExport($slug . '-' . date('Ymd') . '.' . $export, $headers, $rows, $export);
            return;
        }

        if ($export === 'pdf') {
            $this->renderReportPrint($title, $description, $headers, $rows);
            return;
        }

        $this->render('reports/analytics-table', [
            'title' => $title,
            'reportTitle' => $title,
            'description' => $description,
            'slug' => $slug,
            'method' => $method,
            'headers' => $headers,
            'rows' => $rows,
            'chartKeys' => $chartKeys,
        ]);
    }

    private function streamReportExport(string $filename, array $headers, array $rows, string $format): void
    {
        AuditLog::record('report_export', 'Exported report: ' . $filename, 'Report');
        if ($format === 'xls') {
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo "<table border=\"1\"><thead><tr>";
            foreach ($headers as $header) { echo '<th>' . e((string)$header) . '</th>'; }
            echo '</tr></thead><tbody>';
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $cell) { echo '<td>' . e((string)$cell) . '</td>'; }
                echo '</tr>';
            }
            echo '</tbody></table>';
            exit;
        }

        $this->streamGenericCsv($filename, $headers, $rows);
    }

    private function renderReportPrint(string $title, string $description, array $headers, array $rows): void
    {
        $company = current_company() ?? [];
        $settings = new Setting();
        $footer = $settings->value('document_letterhead_footer', '');
        $signatory = $settings->value('document_default_signatory_name', '');
        $signatoryTitle = $settings->value('document_default_signatory_title', '');
        require BASE_PATH . '/app/views/reports/print.php';
        exit;
    }

    private function streamYtdCsv(array $rows, int $year): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ytd-payroll-' . $year . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee', 'Employee #', 'Month', 'Gross Pay', 'Total Deductions', 'Net Pay']);
        $months = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['employee_name'], $r['employee_number'],
                $months[(int) $r['month_num']] . ' ' . $year,
                number_format((float) $r['gross'], 2),
                number_format((float) $r['deductions'], 2),
                number_format((float) $r['net'], 2),
            ]);
        }
        fclose($out);
        exit;
    }

    private function streamContractCsv(array $rows, string $from, string $to): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contract-expiry-' . $from . '-to-' . $to . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee', 'Employee #', 'Designation', 'Contract No.', 'Start Date', 'End Date', 'Days Remaining', 'Status']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['employee_name'], $r['employee_number'], $r['designation'] ?? '',
                $r['contract_number'] ?? '', $r['start_date'] ?? '', $r['end_date'] ?? '',
                (int) $r['days_remaining'], $r['status'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    private function missingEmployeeCompletionItems(array $employee, array $uploadedTypes, array $requiredDocs): array
    {
        $fields = [
            'Email' => $employee['email'] ?? null,
            'Phone' => $employee['phone'] ?? null,
            'Gender' => $employee['gender_id'] ?? null,
            'Date of birth' => $employee['date_of_birth'] ?? null,
            'NRC number' => $employee['nrc_number'] ?? null,
            'NAPSA number' => $employee['napsa_number'] ?? null,
            'TPIN' => $employee['tpin'] ?? null,
            'Bank name' => $employee['bank_name'] ?? null,
            'Bank account number' => $employee['bank_account_number'] ?? null,
            'Address' => $employee['address'] ?? null,
        ];
        $missing = [];
        foreach ($fields as $label => $value) {
            if (trim((string) ($value ?? '')) === '') {
                $missing[] = $label;
            }
        }
        foreach ($requiredDocs as $type) {
            if (!in_array($type, $uploadedTypes, true)) {
                $missing[] = $type . ' document';
            }
        }

        return $missing;
    }

    private function streamGenericCsv(string $filename, array $header, array $rows): void
    {
        AuditLog::record('report_export', 'Exported report CSV: ' . $filename, 'Report');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

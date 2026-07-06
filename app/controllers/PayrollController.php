<?php

declare(strict_types=1);

/**
 * Payroll processing controller.
 */

class PayrollController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        $model = new PayrollRun();
        $search = trim((string) $this->input('search', ''));
        $runs = $search === '' ? $model->listWithDetails() : $model->search($search);

        $this->render('payroll/index', [
            'title' => 'Payroll Processing',
            'runs' => $runs,
            'search' => $search,
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $this->render('payroll/create', [
            'title' => 'Create Payroll Run',
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old' => $_SESSION['_old_payroll_input'] ?? [],
            'taxYears' => (new PayrollRun())->taxYears(),
        ]);

        unset($_SESSION['_old_payroll_input']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/create');
        }

        $data = $this->collectInput();
        $_SESSION['_old_payroll_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('payroll/create');
        }

        $model = new PayrollRun();
        if ($model->periodExists($data['pay_period'])) {
            Session::flash('error', 'Payroll period already exists.');
            redirect('payroll/create');
        }

        try {
            $model->insert($data);
            unset($_SESSION['_old_payroll_input']);
            Session::flash('success', 'Payroll run created successfully.');
            redirect('payroll/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to create payroll run.');
            redirect('payroll/create');
        }
    }

    public function process(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $model = new PayrollRun();
        $paymentModel = new PayrollRunPayment();
        $run = $model->find($runId);

        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        if ((int) ($run['is_locked'] ?? 0) === 1) {
            Session::flash('error', 'This payroll run is locked. Create a correction run instead of regenerating it.');
            redirect('payroll/edit/' . $runId);
        }

        try {
            $summary = $model->generatePayrollItems($runId);

            AuditLog::record('payroll_generate', "Generated payroll items for run #{$runId} ({$run['pay_period']}): {$summary['employees']} employees.", 'PayrollRun', $runId);
            Session::flash('success', 'Payroll generated for ' . (int) $summary['employees'] . ' employees. Review items, then submit for approval.');
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Payroll generation failed for run %d: %s in %s:%d',
                $runId,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            Session::flash('error', 'Failed to generate payroll items: ' . $exception->getMessage());
        }

        redirect('payroll/edit/' . $runId);
    }

    public function edit(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        $model = new PayrollRun();
        $run = $model->find($runId);

        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        $paymentModel = new PayrollRunPayment();
        $paymentSummary = ['payment_count' => 0, 'paid_total' => 0.0, 'latest_payment_date' => null];
        $paymentHistory = [];
        $nextPaymentReference = 'PAY-' . str_pad((string) $runId, 6, '0', STR_PAD_LEFT) . '-001';

        try {
            $paymentSummary = $paymentModel->summaryForRun($runId);
            $paymentHistory = $paymentModel->listForRun($runId);
            $nextPaymentReference = $paymentModel->generateReferenceForRun($runId);
        } catch (Throwable $exception) {
            error_log('Payroll edit payment panel unavailable for run ' . $runId . ': ' . $exception->getMessage());
        }

        $this->render('payroll/edit', [
            'title' => 'Edit Payroll Run',
            'run' => $run,
            'runItems' => $model->itemsForRun($runId),
            'paymentSummary' => $paymentSummary,
            'paymentHistory' => $paymentHistory,
            'calculationHistory' => $model->calculationHistory($runId),
            'adjustmentHistory' => $model->adjustmentsForRun($runId),
            'reversalHistory' => $model->reversalHistory($runId),
            'nextPaymentReference' => $nextPaymentReference,
            'csrf' => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
            'old' => $_SESSION['_old_payroll_input'] ?? [],
            'paymentOld' => $_SESSION['_old_payment_input'] ?? [],
            'statusOptions' => $this->statusOptions(),
            'taxYears' => $model->taxYears(),
            'workflow' => (new WorkflowDefinition())->findByType('payroll'),
        ]);

        unset($_SESSION['_old_payroll_input']);
        unset($_SESSION['_old_payment_input']);
    }

    public function napsaReturn(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        try {
            $path = (new NapsaReturnExporter())->exportForPayrollRun($runId);
        } catch (Throwable $e) {
            Session::flash('error', 'NAPSA return could not be generated: ' . $e->getMessage());
            redirect('payroll/edit/' . $runId);
        }

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

    public function update(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $model = new PayrollRun();
        $existing = $model->find($runId);

        if (!$existing) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        if ((int) ($existing['is_locked'] ?? 0) === 1) {
            Session::flash('error', 'This payroll run is locked and cannot be edited. Use the reversal/correction workflow.');
            redirect('payroll/edit/' . $runId);
        }

        $data = $this->collectInput();
        $_SESSION['_old_payroll_input'] = $data;

        $error = $this->validateInput($data);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('payroll/edit/' . $runId);
        }

        if ($model->periodExists($data['pay_period'], $runId)) {
            Session::flash('error', 'Payroll period already exists.');
            redirect('payroll/edit/' . $runId);
        }

        $data['status'] = $existing['status'];
        $data['total_gross'] = $existing['total_gross'];
        $data['total_deductions'] = $existing['total_deductions'];
        $data['total_net'] = $existing['total_net'];
        $data['created_by'] = $existing['created_by'];
        $data['approved_by_hr'] = $existing['approved_by_hr'];
        $data['approved_by_finance'] = $existing['approved_by_finance'];
        $data['approved_by_admin'] = $existing['approved_by_admin'];

        try {
            $model->update($runId, $data);
            unset($_SESSION['_old_payroll_input']);
            Session::flash('success', 'Payroll run updated successfully.');
            redirect('payroll/index');
        } catch (PDOException) {
            Session::flash('error', 'Failed to update payroll run.');
            redirect('payroll/edit/' . $runId);
        }
    }

    public function delete(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/index');
        }

        $model = new PayrollRun();
        $run = $model->find($runId);

        if ($run && (int) ($run['is_locked'] ?? 0) === 1) {
            Session::flash('error', 'Locked payroll runs cannot be deleted. Use reversal/correction instead.');
            redirect('payroll/index');
        }

        try {
            $model->delete($runId);
            Session::flash('success', 'Payroll run deleted successfully.');
        } catch (PDOException) {
            Session::flash('error', 'Failed to delete payroll run.');
        }

        redirect('payroll/index');
    }

    public function approve(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run.');
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $model  = new PayrollRun();
        $run    = $model->find($runId);
        $user   = current_user();
        $userId = (int) ($user['id'] ?? 0);
        $workflow = new WorkflowDefinition();

        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        $current = (string) $run['status'];

        $transitions = [
            'Draft'            => ['next' => 'HR Approved',      'workflow_step' => 1, 'fallback_role' => 'HR Officer',      'field' => 'approved_by_hr'],
            'HR Approved'      => ['next' => 'Finance Approved',  'workflow_step' => 2, 'fallback_role' => 'Finance Officer', 'field' => 'approved_by_finance'],
            'Finance Approved' => ['next' => 'Admin Approved',    'workflow_step' => 3, 'fallback_role' => 'Super Admin',      'field' => 'approved_by_admin'],
            'Admin Approved'   => ['next' => 'Posted',            'workflow_step' => 4, 'fallback_role' => 'Finance Officer', 'field' => null, 'lock' => true],
        ];

        if (!isset($transitions[$current])) {
            Session::flash('error', "This payroll run cannot be approved from status: {$current}.");
            redirect('payroll/edit/' . $runId);
        }

        $t = $transitions[$current];
        $stepOrder = (int) $t['workflow_step'];
        $requiredRole = $workflow->requiredRoleFor('payroll', $stepOrder, (string) $t['fallback_role']);
        if (!$this->userMatchesWorkflowRole($requiredRole)) {
            Session::flash('error', "This workflow step requires {$requiredRole} approval.");
            redirect('payroll/edit/' . $runId);
        }

        if (count($model->itemsForRun($runId)) === 0) {
            Session::flash('error', 'Generate payroll items before submitting for approval.');
            redirect('payroll/edit/' . $runId);
        }

        $updateData = [
            'pay_period'          => $run['pay_period'],
            'run_date'            => $run['run_date'],
            'status'              => $t['next'],
            'total_gross'         => $run['total_gross'],
            'total_deductions'    => $run['total_deductions'],
            'total_net'           => $run['total_net'],
            'created_by'          => $run['created_by'],
            'approved_by_hr'      => $run['approved_by_hr'],
            'approved_by_finance' => $run['approved_by_finance'],
            'approved_by_admin'   => $run['approved_by_admin'],
        ];

        if ($t['field'] !== null) {
            $updateData[$t['field']] = $userId;
        }

        $model->update($runId, $updateData);
        if (!empty($t['lock'])) {
            $model->lockRun($runId, $userId);
        }
        $label = $workflow->actionLabelFor('payroll', $stepOrder, 'Approve Payroll');
        WorkflowEvent::record('payroll', 'PayrollRun', $runId, $current, (string) $t['next'], 'payroll_approve', $label);
        AuditLog::record('payroll_approve', "Approved payroll run #{$runId} ({$run['pay_period']}) from '{$current}' to '{$t['next']}'.", 'PayrollRun', $runId);
        Session::flash('success', "Payroll run approved: status is now '{$t['next']}'.");
        redirect('payroll/edit/' . $runId);
    }

    public function addCustomDeduction(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/edit/' . (int) $id);
        }

        $runId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $employeeId = (int) $this->input('employee_id', 0);
        $label = trim((string) $this->input('label', ''));
        $amount = $this->normalizeMoney((string) $this->input('amount', '0'));
        $reason = trim((string) $this->input('reason', ''));

        try {
            (new PayrollRun())->addEmployeeDeductionAdjustment(
                $runId,
                $employeeId,
                $label,
                $amount,
                $reason,
                (int) (current_user()['id'] ?? 0)
            );
            AuditLog::record('payroll_custom_deduction', "Added custom deduction {$label} to employee #{$employeeId} on payroll run #{$runId}.", 'PayrollRun', $runId);
            Session::flash('success', 'Custom deduction added and payslip recalculated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('payroll/edit/' . $runId);
    }

    public function bankExport(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $runId = (int) $id;
        $model = new PayrollRun();
        $run   = $model->find($runId);

        if (!$run || count($model->itemsForRun($runId)) === 0) {
            Session::flash('error', 'No payroll items found for this run.');
            redirect('payroll/index');
        }

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND pr.company_id = :run_cid AND e.company_id = :employee_cid' : '';
        $stmt = db()->prepare(
            "SELECT e.full_name, e.employee_number, e.bank_name, e.bank_account_number,
                    pi.net_pay, d.name AS department_name
             FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             JOIN employees e ON e.id = pi.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE pi.payroll_run_id = :rid$and
             ORDER BY e.full_name ASC"
        );
        $params = ['rid' => $runId];
        if ($cid > 0) { $params['run_cid'] = $cid; $params['employee_cid'] = $cid; }
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $period   = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $run['pay_period']);
        $filename = "bank-payment-{$period}.csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Employee No.', 'Full Name', 'Department', 'Bank Name', 'Account Number', 'Net Pay (ZMW)', 'Pay Period']);

        foreach ($items as $item) {
            fputcsv($out, [
                $item['employee_number']     ?? '',
                $item['full_name']           ?? '',
                $item['department_name']     ?? '',
                $item['bank_name']           ?? '',
                $item['bank_account_number'] ?? '',
                number_format((float)($item['net_pay'] ?? 0), 2, '.', ''),
                $run['pay_period'],
            ]);
        }
        fclose($out);
        AuditLog::record('bank_export', "Exported bank payment file for run #{$runId} ({$run['pay_period']}).", 'PayrollRun', $runId);
        exit;
    }

    public function emailPayslip(string $runId, string $employeeId): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/edit/' . (int) $runId);
        }

        $runIdInt = (int) $runId;
        $employeeIdInt = (int) $employeeId;

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runIdInt);
        }

        $result = $this->sendPayslipEmail($runIdInt, $employeeIdInt);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('payroll/edit/' . $runIdInt);
    }

    public function preview(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        $runId = (int) $id;
        $model = new PayrollRun();
        $run = $model->find($runId);

        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        try {
            $preview = $model->previewPayrollItems($runId);
        } catch (Throwable $exception) {
            Session::flash('error', 'Failed to preview payroll: ' . $exception->getMessage());
            redirect('payroll/edit/' . $runId);
        }

        $this->render('payroll/preview', [
            'title' => 'Payroll Preview',
            'run' => $run,
            'preview' => $preview,
            'csrf' => Session::csrfToken(),
        ]);
    }

    public function reverse(string $id): void
    {
        require_auth();
        require_role(['Super Admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/edit/' . (int) $id);
        }

        $runId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $reason = trim((string) $this->input('reason', ''));
        if ($reason === '') {
            Session::flash('error', 'Reversal reason is required.');
            redirect('payroll/edit/' . $runId);
        }

        try {
            $correctionId = (new PayrollRun())->reverseRun($runId, $reason, (int) (current_user()['id'] ?? 0));
            AuditLog::record('payroll_reverse', "Reversed payroll run #{$runId}; correction run #{$correctionId} created.", 'PayrollRun', $runId);
            Session::flash('success', 'Payroll run reversed. A correction run has been created.');
            redirect('payroll/edit/' . $correctionId);
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('payroll/edit/' . $runId);
        }
    }

    public function emailAllPayslips(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/edit/' . (int) $id);
        }

        $runId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $items = (new PayrollRun())->itemsForRun($runId);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($items as $item) {
            $employeeId = (int) ($item['employee_id'] ?? 0);
            $result = $this->sendPayslipEmail($runId, $employeeId);
            if ($result['ok']) {
                $sent++;
            } elseif (($result['reason'] ?? '') === 'missing_email') {
                $skipped++;
            } else {
                $failed++;
            }
        }

        $parts = ["{$sent} sent"];
        if ($skipped > 0) { $parts[] = "{$skipped} skipped without email address"; }
        if ($failed > 0) { $parts[] = "{$failed} failed"; }

        Session::flash($failed > 0 ? 'error' : 'success', 'Payslip email run completed: ' . implode(', ', $parts) . '.');
        redirect('payroll/edit/' . $runId);
    }

    private function collectInput(): array
    {
        $user = current_user();
        $payPeriod = $this->payPeriodFromInput();

        return [
            'pay_period' => $payPeriod,
            'run_date' => $this->normalizeDate((string) $this->input('run_date', '')),
            'status' => 'Draft',
            'total_gross' => 0.0,
            'total_deductions' => 0.0,
            'total_net' => 0.0,
            'created_by' => (int) ($user['id'] ?? 0) ?: null,
            'tax_year_id' => (int) $this->input('tax_year_id', 0) ?: null,
            'proration_mode' => (string) $this->input('proration_mode', 'Full Month'),
        ];
    }

    private function validateInput(array $data): ?string
    {
        if ($data['pay_period'] === '' || $data['run_date'] === null) {
            return 'Pay period and run date are required.';
        }

        if (!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/', (string) $data['pay_period'])) {
            return 'Please select a valid payroll month and year.';
        }

        if (!in_array($data['status'], $this->statusOptions(), true)) {
            return 'Invalid payroll status selected.';
        }

        if (!in_array((string) ($data['proration_mode'] ?? ''), ['Full Month', 'Calendar Days'], true)) {
            return 'Invalid payroll proration method selected.';
        }

        return null;
    }

    private function payPeriodFromInput(): string
    {
        $year = (int) $this->input('pay_year', 0);
        $month = (int) $this->input('pay_month', 0);

        if ($year >= 2000 && $year <= 2099 && $month >= 1 && $month <= 12) {
            return sprintf('%04d-%02d', $year, $month);
        }

        return strtoupper(trim((string) $this->input('pay_period', '')));
    }

    private function statusOptions(): array
    {
        return ['Draft', 'HR Approved', 'Finance Approved', 'Admin Approved', 'Posted', 'Partially Paid', 'Paid'];
    }

    public function recordPayment(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if ($runId <= 0) {
            Session::flash('error', 'Invalid payroll run id.');
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        $runModel = new PayrollRun();
        $run = $runModel->find($runId);

        if (!$run) {
            Session::flash('error', 'Payroll run not found.');
            redirect('payroll/index');
        }

        $paymentData = $this->collectPaymentInput();
        $_SESSION['_old_payment_input'] = $paymentData;

        $error = $this->validatePaymentInput($runId, $paymentData);
        if ($error !== null) {
            Session::flash('error', $error);
            redirect('payroll/edit/' . $runId);
        }

        try {
            $paymentModel = new PayrollRunPayment();
            $result = $paymentModel->recordPayment($runId, $paymentData);
            unset($_SESSION['_old_payment_input']);

            $statusMessage = $result['status'] === 'Paid'
                ? 'Payroll run fully paid successfully.'
                : 'Payment recorded. Remaining balance: ' . format_currency((float) $result['balance']);

            Session::flash('success', $statusMessage);
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Payroll payment failed for run %d: %s in %s:%d',
                $runId,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            Session::flash('error', 'Failed to record payment: ' . $exception->getMessage());
        }

        redirect('payroll/edit/' . $runId);
    }

    public function releasePayslips(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        $runId = (int) $id;
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/edit/' . $runId);
        }

        try {
            (new PayrollRun())->releasePayslips($runId, (int) (current_user()['id'] ?? 0));
            AuditLog::record('payslips_release', "Released payslips for payroll run #{$runId}.", 'PayrollRun', $runId);
            Session::flash('success', 'Payslips released to employee portal.');
        } catch (Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('payroll/edit/' . $runId);
    }

    public function recordPayslipPayment(string $itemId): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('payroll/index');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('payroll/index');
        }

        $itemIdInt = (int) $itemId;
        $runId = (int) $this->input('run_id', 0);
        $data = [
            'payment_date' => $this->normalizeDate((string) $this->input('payment_date', '')) ?? date('Y-m-d'),
            'amount' => $this->normalizeMoney((string) $this->input('amount', '0')),
            'payment_method' => trim((string) $this->input('payment_method', 'Bank Transfer')),
            'reference_number' => trim((string) $this->input('reference_number', '')),
            'notes' => trim((string) $this->input('notes', '')),
            'created_by' => (int) (current_user()['id'] ?? 0) ?: null,
        ];

        try {
            (new PayrollItemPayment())->recordItemPayment($itemIdInt, $data);
            AuditLog::record('payslip_payment', 'Recorded payment against payslip item #' . $itemIdInt, 'PayrollItem', $itemIdInt);
            Session::flash('success', 'Payslip payment recorded.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('payroll/edit/' . $runId);
    }

    public function payslip(string $runId, string $employeeId): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $runIdInt = (int) $runId;
        $employeeIdInt = (int) $employeeId;

        if ($runIdInt <= 0 || $employeeIdInt <= 0) {
            Session::flash('error', 'Invalid payslip reference.');
            redirect('payroll/index');
        }

        $model = new PayrollRun();
        $item = $model->itemForRunAndEmployee($runIdInt, $employeeIdInt);

        if (!$item) {
            Session::flash('error', 'Payslip not found for this run and employee.');
            redirect('payroll/edit/' . $runIdInt);
        }

        $runDate = (string) ($item['run_date'] ?? date('Y-m-d'));
        $salaryModel = new EmployeeSalary();
        $bonusModel = new BonusOvertime();

        $salary = $salaryModel->activeWithStructureForDate($employeeIdInt, $runDate) ?: [];
        $bonuses = $bonusModel->forRunAndEmployee($runIdInt, $employeeIdInt);

        $basicPay = (float) ($salary['basic_pay'] ?? 0);
        $housingAllowance = (float) ($salary['housing_allowance'] ?? 0);
        $transportAllowance = (float) ($salary['transport_allowance'] ?? 0);
        $otherAllowances = (float) ($salary['other_allowances'] ?? 0);
        $bonusTotal = 0.0;

        foreach ($bonuses as $bonus) {
            $bonusTotal += (float) ($bonus['amount'] ?? 0);
        }

        $earningsLines = [
            ['label' => 'Basic Salary', 'amount' => $basicPay],
            ['label' => 'Housing Allowance', 'amount' => $housingAllowance],
            ['label' => 'Transport Allowance', 'amount' => $transportAllowance],
            ['label' => 'Other Allowances', 'amount' => $otherAllowances],
            ['label' => 'Bonuses / Overtime', 'amount' => $bonusTotal],
        ];

        $deductionLines = $model->deductionLinesForItem((int) $item['id']);
        $calculatedDeductions = (float) ($item['total_deductions'] ?? 0);
        $grossForDeduction = $basicPay + $housingAllowance + $transportAllowance + $otherAllowances + $bonusTotal;

        $downloadName = strtolower(preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($item['employee_number'] ?? 'employee')) . '-' . preg_replace('/[^A-Za-z0-9_-]/', '-', (string) ($item['pay_period'] ?? 'pay-period')));
        $downloadName = trim($downloadName, '-');
        $downloadName = $downloadName === '' ? 'payslip' : $downloadName;

        $company = current_company() ?? [];

        $this->renderAuth('payroll/payslip', [
            'title' => 'Payslip',
            'companyName' => (string) ($company['name'] ?? app_product_name()),
            'companyAddress' => (string) ($company['address'] ?? 'Payroll Office'),
            'companyLogoUrl' => company_logo_url($company),
            'item' => $item,
            'salary' => $salary,
            'earningsLines' => $earningsLines,
            'deductionLines' => $deductionLines,
            'grossEarnings' => $grossForDeduction,
            'totalDeductions' => $calculatedDeductions,
            'netPay' => (float) ($item['net_pay'] ?? 0),
            'downloadName' => $downloadName,
            'csrf' => Session::csrfToken(),
        ]);
    }

    private function sendPayslipEmail(int $runId, int $employeeId): array
    {
        $model = new PayrollRun();
        $item = $model->itemForRunAndEmployee($runId, $employeeId);

        if (!$item) {
            return ['ok' => false, 'message' => 'Payslip not found.', 'reason' => 'not_found'];
        }

        $email = trim((string) ($item['employee_email'] ?? ''));
        if ($email === '') {
            return [
                'ok' => false,
                'message' => 'Employee has no email address on file.',
                'reason' => 'missing_email',
            ];
        }

        $company = current_company() ?? [];
        $companyName = (string) ($company['name'] ?? 'Payroll Office');
        $employeeName = (string) ($item['employee_name'] ?? 'Employee');
        $period = (string) ($item['pay_period'] ?? '');

        $emailTemplates = new CompanyEmailTemplate();
        $tokens = $this->payslipEmailTokens($item, $company);
        $html = $emailTemplates->renderBody('payslip', $tokens);
        $subject = $emailTemplates->renderSubject('payslip', $tokens);
        $attachments = [];

        if ($emailTemplates->template('payslip')['attach_document']) {
            $safePeriod = preg_replace('/[^A-Za-z0-9_-]+/', '-', $period) ?: 'period';
            $safeEmployee = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($item['employee_number'] ?? $employeeName)) ?: 'employee';
            $attachments[] = [
                'filename' => "payslip-{$safeEmployee}-{$safePeriod}.html",
                'mime' => 'text/html; charset=UTF-8',
                'content' => $this->buildPayslipAttachmentHtml($item, $companyName),
            ];
        }

        $mailer = (new ContractNotification())->buildMailer();
        $ok = $mailer->send($email, $employeeName, $subject, $html, $attachments);

        if ($ok) {
            AuditLog::record('payslip_email', "Payslip emailed to {$email} for {$employeeName} ({$period}).", 'PayrollRun', $runId);
            return ['ok' => true, 'message' => "Payslip emailed to {$employeeName} ({$email})."];
        }

        return [
            'ok' => false,
            'message' => "Failed to email payslip to {$employeeName} ({$email}). " . ($mailer->lastError() !== '' ? $mailer->lastError() : 'Check SMTP settings.'),
            'reason' => 'send_failed',
        ];
    }

    private function payslipEmailTokens(array $item, array $company): array
    {
        return [
            'company_name' => (string) ($company['name'] ?? app_product_name()),
            'company_email' => (string) ($company['email'] ?? ''),
            'company_phone' => (string) ($company['phone'] ?? ''),
            'company_address' => (string) ($company['address'] ?? ''),
            'employee_name' => (string) ($item['employee_name'] ?? 'Employee'),
            'employee_number' => (string) ($item['employee_number'] ?? ''),
            'employee_email' => (string) ($item['employee_email'] ?? ''),
            'pay_period' => (string) ($item['pay_period'] ?? ''),
            'gross_pay' => format_currency((float) ($item['gross_pay'] ?? 0)),
            'total_deductions' => format_currency((float) ($item['total_deductions'] ?? 0)),
            'net_pay' => format_currency((float) ($item['net_pay'] ?? 0)),
            'today' => date('d M Y'),
        ];
    }

    private function buildPayslipAttachmentHtml(array $item, string $companyName): string
    {
        $employeeName = e((string) ($item['employee_name'] ?? 'Employee'));
        $employeeNo = e((string) ($item['employee_number'] ?? ''));
        $period = e((string) ($item['pay_period'] ?? ''));
        $runDate = e((string) ($item['run_date'] ?? ''));
        $gross = e(format_currency((float) ($item['gross_pay'] ?? 0)));
        $deductions = e(format_currency((float) ($item['total_deductions'] ?? 0)));
        $net = e(format_currency((float) ($item['net_pay'] ?? 0)));
        $company = e($companyName);

        return <<<HTML
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Payslip {$period}</title>
            <style>
                body{font-family:Arial,sans-serif;color:#111827;margin:36px}
                .header{border-bottom:2px solid #1a3a2a;padding-bottom:14px;margin-bottom:24px}
                table{width:100%;border-collapse:collapse}
                td{padding:10px;border-bottom:1px solid #e5e7eb}
                td:first-child{font-weight:700;color:#475569}
                .net td{background:#f0fdf4;font-weight:700;color:#166534}
                @media print{body{margin:24mm}}
            </style>
        </head>
        <body>
            <div class="header">
                <h1 style="margin:0">{$company}</h1>
                <div style="color:#475569;font-size:13px">Payslip | {$period} | {$employeeName}</div>
            </div>
            <table>
                <tr><td>Employee</td><td>{$employeeName}</td></tr>
                <tr><td>Employee No.</td><td>{$employeeNo}</td></tr>
                <tr><td>Run Date</td><td>{$runDate}</td></tr>
                <tr><td>Gross Pay</td><td>{$gross}</td></tr>
                <tr><td>Total Deductions</td><td>{$deductions}</td></tr>
                <tr class="net"><td>Net Pay</td><td>{$net}</td></tr>
            </table>
            <p style="font-size:12px;color:#64748b;margin-top:22px">This is a computer-generated payslip.</p>
        </body>
        </html>
        HTML;
    }

    private function buildPayslipEmailHtml(array $item, string $companyName): string
    {
        $employeeName = e((string) ($item['employee_name'] ?? ''));
        $employeeNo = e((string) ($item['employee_number'] ?? ''));
        $period = e((string) ($item['pay_period'] ?? ''));
        $runDate = e((string) ($item['run_date'] ?? ''));
        $gross = e(format_currency((float) ($item['gross_pay'] ?? 0)));
        $deductions = e(format_currency((float) ($item['total_deductions'] ?? 0)));
        $net = e(format_currency((float) ($item['net_pay'] ?? 0)));
        $company = e($companyName);

        return <<<HTML
        <html><body style="font-family:Arial,sans-serif;color:#1f2937;background:#f8fafc;padding:24px">
            <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
                <div style="background:#1a3a2a;color:#fff;padding:18px 22px">
                    <h2 style="margin:0;font-size:20px">Payslip</h2>
                    <div style="opacity:.85;font-size:13px">{$company}</div>
                </div>
                <div style="padding:22px">
                    <p>Hello <strong>{$employeeName}</strong>,</p>
                    <p>Your payslip for <strong>{$period}</strong> is ready. Summary details are below.</p>
                    <table style="width:100%;border-collapse:collapse;margin-top:14px">
                        <tr><td style="padding:9px;border-bottom:1px solid #e5e7eb;font-weight:bold">Employee No.</td><td style="padding:9px;border-bottom:1px solid #e5e7eb">{$employeeNo}</td></tr>
                        <tr><td style="padding:9px;border-bottom:1px solid #e5e7eb;font-weight:bold">Run Date</td><td style="padding:9px;border-bottom:1px solid #e5e7eb">{$runDate}</td></tr>
                        <tr><td style="padding:9px;border-bottom:1px solid #e5e7eb;font-weight:bold">Gross Pay</td><td style="padding:9px;border-bottom:1px solid #e5e7eb">{$gross}</td></tr>
                        <tr><td style="padding:9px;border-bottom:1px solid #e5e7eb;font-weight:bold">Total Deductions</td><td style="padding:9px;border-bottom:1px solid #e5e7eb">{$deductions}</td></tr>
                        <tr><td style="padding:12px 9px;font-weight:bold;background:#f0fdf4">Net Pay</td><td style="padding:12px 9px;font-weight:bold;background:#f0fdf4;color:#166534">{$net}</td></tr>
                    </table>
                    <p style="font-size:12px;color:#64748b;margin-top:18px">This is a computer-generated payroll email from {$company}.</p>
                </div>
            </div>
        </body></html>
        HTML;
    }

    private function normalizeDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $date = date_create($trimmed);

        return $date === false ? null : $date->format('Y-m-d');
    }

    private function normalizeMoney(string $value): float
    {
        $normalized = trim($value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function userMatchesWorkflowRole(string $requiredRole): bool
    {
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        return $role === 'Super Admin'
            || $accessLevel === 'Super Admin'
            || $role === $requiredRole
            || $accessLevel === $requiredRole;
    }

    private function collectPaymentInput(): array
    {
        $user = current_user();

        return [
            'payment_date' => $this->normalizeDate((string) $this->input('payment_date', '')) ?? date('Y-m-d'),
            'amount' => $this->normalizeMoney((string) $this->input('amount', '0')),
            'payment_method' => trim((string) $this->input('payment_method', 'Cash')),
            'reference_number' => trim((string) $this->input('reference_number', '')),
            'notes' => trim((string) $this->input('notes', '')),
            'created_by' => (int) ($user['id'] ?? 0) ?: null,
        ];
    }

    private function validatePaymentInput(int $runId, array $data): ?string
    {
        if (($data['payment_date'] ?? '') === '') {
            return 'Payment date is required.';
        }

        if ((float) ($data['amount'] ?? 0) <= 0) {
            return 'Payment amount must be greater than zero.';
        }

        $runModel = new PayrollRun();
        $paymentModel = new PayrollRunPayment();
        $run = $runModel->find($runId);

        if (!$run) {
            return 'Payroll run not found.';
        }

        if (count((new PayrollRun())->itemsForRun($runId)) === 0) {
            return 'Generate payroll items before recording a payment.';
        }

        $summary = $paymentModel->summaryForRun($runId);
        $balance = max(0.0, (float) ($run['total_net'] ?? 0) - (float) ($summary['paid_total'] ?? 0));

        if ($balance <= 0.0) {
            return 'This payroll run is already fully paid.';
        }

        if ((float) $data['amount'] > $balance) {
            return 'Payment amount cannot exceed the remaining balance of ' . format_currency($balance) . '.';
        }

        return null;
    }
}

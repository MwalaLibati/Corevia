<?php

declare(strict_types=1);

/**
 * Employee self-service portal.
 * Routes: /portal/<method>/<params>
 */

class PortalController extends Controller
{
    public function login(): void
    {
        if (is_employee_logged_in()) {
            redirect('portal/dashboard');
        }

        $this->renderAuth('portal/login', [
            'csrf'         => Session::csrfToken(),
            'flashError'   => Session::flash('error'),
            'flashSuccess' => Session::flash('success'),
        ]);
    }

    public function loginStore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('portal/login');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('portal/login');
        }

        $employeeNumber = strtoupper(trim((string) $this->input('employee_number', '')));
        $password       = (string) $this->input('password', '');
        $ipAddress      = $this->clientIp();
        $limiter        = new LoginAttempt();

        if ($employeeNumber === '' || $password === '') {
            Session::flash('error', 'Employee number and password are required.');
            redirect('portal/login');
        }

        if ($limiter->isLocked('employee_portal', $employeeNumber, $ipAddress)) {
            $minutes = $limiter->remainingLockMinutes('employee_portal', $employeeNumber, $ipAddress);
            Session::flash('error', "Too many failed login attempts. Please try again in {$minutes} minute(s).");
            redirect('portal/login');
        }

        $stmt = db()->prepare(
            "SELECT * FROM employees WHERE UPPER(employee_number) = :en AND portal_active = 1 LIMIT 1"
        );
        $stmt->execute(['en' => $employeeNumber]);
        $employee = $stmt->fetch();

        if (!$employee || !password_verify($password, (string) ($employee['portal_password_hash'] ?? ''))) {
            $limiter->record('employee_portal', $employeeNumber, false, $ipAddress);
            Session::flash('error', 'Invalid employee number or password.');
            redirect('portal/login');
        }

        if (!empty($employee['portal_must_change_password'])
            && !empty($employee['portal_password_expires_at'])
            && strtotime((string) $employee['portal_password_expires_at']) < time()) {
            $limiter->record('employee_portal', $employeeNumber, false, $ipAddress);
            Session::flash('error', 'Your one-time password has expired. Please contact HR for a new portal password.');
            redirect('portal/login');
        }

        $limiter->record('employee_portal', $employeeNumber, true, $ipAddress);
        $limiter->clearFailures('employee_portal', $employeeNumber, $ipAddress);
        Session::regenerate();

        $_SESSION['emp_user'] = [
            'id'              => (int) $employee['id'],
            'employee_number' => (string) $employee['employee_number'],
            'full_name'       => (string) $employee['full_name'],
            'designation'     => (string) ($employee['designation'] ?? ''),
            'department_id'   => (int) ($employee['department_id'] ?? 0),
            'company_id'      => (int) ($employee['company_id'] ?? 0),
            'portal_must_change_password' => (int) ($employee['portal_must_change_password'] ?? 0),
        ];

        db()->prepare('UPDATE employees SET portal_last_login_at = NOW() WHERE id = :id')
            ->execute(['id' => (int) $employee['id']]);

        // Set active tenant
        $cid = (int) ($employee['company_id'] ?? 0);
        if ($cid > 0) {
            $compStmt = db()->prepare("SELECT * FROM companies WHERE id = :id AND is_active = 1 LIMIT 1");
            $compStmt->execute(['id' => $cid]);
            $company = $compStmt->fetch();
            if ($company) {
                Tenant::set($company);
            }
        }

        redirect('portal/dashboard');
    }

    public function logout(): void
    {
        unset($_SESSION['emp_user']);
        Session::flash('success', 'You have been logged out.');
        redirect('portal/login');
    }

    public function dashboard(): void
    {
        require_employee_auth();
        $emp   = current_employee();
        $empId = (int) $emp['id'];
        $year  = (int) date('Y');

        $employeeStmt = db()->prepare(
            "SELECT e.*, d.name AS department_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE e.id = :id LIMIT 1"
        );
        $employeeStmt->execute(['id' => $empId]);
        $employee = $employeeStmt->fetch() ?: $emp;

        $countStmt = db()->prepare(
            "SELECT COUNT(*) FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             WHERE pi.employee_id = :id AND pr.payslips_released = 1"
        );
        $countStmt->execute(['id' => $empId]);
        $payslipCount = (int) $countStmt->fetchColumn();

        $latestStmt = db()->prepare(
            "SELECT pi.*, pr.run_date, pr.pay_period, pr.status AS run_status
             FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             WHERE pi.employee_id = :id AND pr.payslips_released = 1
             ORDER BY pr.run_date DESC LIMIT 1"
        );
        $latestStmt->execute(['id' => $empId]);
        $latestPayslip = $latestStmt->fetch() ?: null;

        $contractStmt = db()->prepare(
            "SELECT * FROM employee_contracts WHERE employee_id = :id AND approval_status = 'Approved' ORDER BY start_date DESC LIMIT 1"
        );
        $contractStmt->execute(['id' => $empId]);
        $contract = $contractStmt->fetch() ?: null;

        $napsaTotals = $this->employeeNapsaContributionTotals($empId);
        $activeSalary = (new EmployeeSalary())->activeWithStructureForDate($empId, date('Y-m-d'));
        $gratuity = $this->estimatePortalGratuity($contract, $activeSalary, $this->configuredGratuityPolicy());
        $isPermanent = strtolower(trim((string) ($employee['employment_type'] ?? $contract['contract_type'] ?? ''))) === 'permanent';
        $contractExpiry = $this->contractExpirySummary($contract);

        $deductStmt = db()->prepare(
            "SELECT ed.*, dt.name AS deduction_name, dt.code, dt.calculation_type
             FROM employee_deductions ed
             JOIN deduction_types dt ON dt.id = ed.deduction_type_id
             WHERE ed.employee_id = :id AND ed.is_active = 1
             ORDER BY dt.name ASC"
        );
        $deductStmt->execute(['id' => $empId]);
        $deductions = $deductStmt->fetchAll();

        $leaveTypes  = $this->activeLeaveTypesForPortal();
        $leaveModel  = new LeaveRequest();
        $leaveBalances = [];
        foreach ($leaveTypes as $lt) {
            $leaveBalances[(int)$lt['id']] = $leaveModel->getBalance($empId, (int)$lt['id'], $year);
        }

        $advModel      = new SalaryAdvance();
        $activeAdvance = $advModel->activeForEmployee($empId);
        $pendStmt      = db()->prepare("SELECT * FROM salary_advances WHERE employee_id=:eid AND status='Pending' LIMIT 1");
        $pendStmt->execute(['eid' => $empId]);
        $pendingAdvance = $pendStmt->fetch() ?: null;

        $announcements = array_slice((new Announcement())->published(), 0, 3);

        $upStmt = db()->prepare(
            "SELECT lr.*, lt.name AS leave_type_name
             FROM leave_requests lr
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.employee_id=:eid AND lr.status='Approved' AND lr.start_date >= CURDATE()
             ORDER BY lr.start_date ASC LIMIT 3"
        );
        $upStmt->execute(['eid' => $empId]);
        $upcomingLeave = $upStmt->fetchAll();

        $totalLeaveRemaining = 0.0;
        foreach ($leaveTypes as $lt) {
            $b = $leaveBalances[(int)$lt['id']] ?? ['entitled_days' => (float)$lt['days_per_year'], 'used_days' => 0];
            $totalLeaveRemaining += max(0, (float)$b['entitled_days'] - (float)$b['used_days']);
        }

        $this->renderPortal('portal/dashboard', [
            'emp'                  => $emp,
            'employee'             => $employee,
            'payslipCount'         => $payslipCount,
            'latestPayslip'        => $latestPayslip,
            'contract'             => $contract,
            'contractExpiry'       => $contractExpiry,
            'deductions'           => $deductions,
            'leaveTypes'           => $leaveTypes,
            'leaveBalances'        => $leaveBalances,
            'activeAdvance'        => $activeAdvance,
            'pendingAdvance'       => $pendingAdvance,
            'announcements'        => $announcements,
            'upcomingLeave'        => $upcomingLeave,
            'totalLeaveRemaining'  => $totalLeaveRemaining,
            'napsaTotals'          => $napsaTotals,
            'gratuityEstimate'     => $gratuity,
            'showGratuityCard'     => !$isPermanent,
            'profileCompletion'    => $this->profileCompletion($empId),
        ]);
    }

    public function payslips(): void
    {
        require_employee_auth();
        $empId = (int) (current_employee()['id']);

        $paymentSelect = '0 AS paid_amount, pi.net_pay AS balance_due';
        $paymentJoin = '';
        if ($this->tableExists('payroll_item_payments')) {
            $paymentSelect = 'COALESCE(pay.paid_amount, 0) AS paid_amount,
                    GREATEST(0, pi.net_pay - COALESCE(pay.paid_amount, 0)) AS balance_due';
            $paymentJoin = "LEFT JOIN (
                SELECT payroll_item_id, SUM(amount) AS paid_amount
                FROM payroll_item_payments
                GROUP BY payroll_item_id
             ) pay ON pay.payroll_item_id = pi.id";
        }

        $stmt = db()->prepare(
            "SELECT pi.*, pr.run_date, pr.pay_period, pr.status AS run_status,
                    {$paymentSelect}
             FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             {$paymentJoin}
             WHERE pi.employee_id = :id AND pr.payslips_released = 1
             ORDER BY pr.run_date DESC"
        );
        $stmt->execute(['id' => $empId]);
        $payslips = $stmt->fetchAll();

        $this->renderPortal('portal/payslips', [
            'emp'      => current_employee(),
            'payslips' => $payslips,
        ]);
    }

    public function payslipView(string $id = '0'): void
    {
        require_employee_auth();
        $empId     = (int) (current_employee()['id']);
        $payslipId = (int) $id;

        $stmt = db()->prepare(
            "SELECT pi.*, pr.run_date, pr.pay_period, pr.status AS run_status,
                    e.full_name, e.employee_number, e.designation,
                    d.name AS department_name
             FROM payroll_items pi
             JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
             JOIN employees e    ON e.id  = pi.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE pi.id = :pid AND pi.employee_id = :eid AND pr.payslips_released = 1
             LIMIT 1"
        );
        $stmt->execute(['pid' => $payslipId, 'eid' => $empId]);
        $slip = $stmt->fetch();

        if (!$slip) {
            Session::flash('error', 'Payslip not found.');
            redirect('portal/payslips');
        }

        $deductStmt = db()->prepare(
            "SELECT ed.amount, dt.name AS deduction_name, dt.calculation_type, dt.is_statutory
             FROM employee_deductions ed
             JOIN deduction_types dt ON dt.id = ed.deduction_type_id
             WHERE ed.employee_id = :eid AND ed.is_active = 1"
        );
        $deductStmt->execute(['eid' => $empId]);
        $deductions = $deductStmt->fetchAll();

        $this->renderPortal('portal/payslip-view', [
            'emp'        => current_employee(),
            'slip'       => $slip,
            'deductions' => $deductions,
        ]);
    }

    public function contract(): void
    {
        require_employee_auth();
        $empId = (int) (current_employee()['id']);
        $requestModel = new ContractRenewalRequest();

        $stmt = db()->prepare(
            "SELECT ec.*, e.full_name, e.employee_number, e.designation
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             WHERE ec.employee_id = :id
               AND ec.approval_status = 'Approved'
             ORDER BY ec.start_date DESC"
        );
        $stmt->execute(['id' => $empId]);
        $contracts = $stmt->fetchAll();
        $pendingRequests = [];
        foreach ($requestModel->pendingForEmployee($empId) as $request) {
            $pendingRequests[(int) $request['contract_id']] = $request;
        }

        $this->renderPortal('portal/contract', [
            'emp'             => current_employee(),
            'contracts'       => $contracts,
            'pendingRequests' => $pendingRequests,
            'csrf'            => Session::csrfToken(),
            'flashSuccess'    => Session::flash('success'),
            'flashError'      => Session::flash('error'),
        ]);
    }

    public function contractRenewalRequest(string $id = '0'): void
    {
        require_employee_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('portal/contract');
        }

        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('portal/contract');
        }

        $empId = (int) current_employee()['id'];
        $contractId = (int) $id;
        $contract = (new EmployeeContract())->findDetailed($contractId);

        if (!$contract || (int) ($contract['employee_id'] ?? 0) !== $empId || (string) ($contract['approval_status'] ?? '') !== 'Approved') {
            Session::flash('error', 'Contract not found.');
            redirect('portal/contract');
        }

        $requestedEndDate = $this->normalizeDate((string) $this->input('requested_end_date', ''));
        $reason = trim((string) $this->input('reason', ''));
        $reason = $reason !== '' ? $reason : null;

        try {
            $requestId = (new ContractRenewalRequest())->submit($empId, $contractId, $requestedEndDate, $reason);
            AuditLog::record('contract_renewal_request', 'Employee requested contract renewal.', 'ContractRenewalRequest', $requestId, 'employee', [
                'contract_id' => $contractId,
                'requested_end_date' => $requestedEndDate,
            ]);
            Session::flash('success', 'Your contract renewal request has been submitted to HR.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('portal/contract');
    }

    public function contractView(string $id = '0'): void
    {
        require_employee_auth();
        $empId = (int) current_employee()['id'];
        $contractId = (int) $id;

        $contract = (new EmployeeContract())->findDetailed($contractId);
        if (!$contract || (int) ($contract['employee_id'] ?? 0) !== $empId || (string) ($contract['approval_status'] ?? '') !== 'Approved') {
            Session::flash('error', 'Contract not found.');
            redirect('portal/contract');
        }

        $employee = (new Employee())->findDetailed($empId) ?? [];
        $template = null;
        $renderedBody = null;
        $missingFields = [];
        $tmplModel = new ContractTemplate();
        $templateId = (int) ($contract['template_id'] ?? 0);
        if ($templateId > 0) {
            $template = $tmplModel->findDetailed($templateId);
        }
        if (!$template) {
            $empSalary = (new EmployeeSalary())->activeWithStructureForDate($empId, (string)($contract['start_date'] ?? date('Y-m-d')));
            $template = $tmplModel->resolve(
                $empSalary ? (int) ($empSalary['salary_structure_id'] ?? 0) : null,
                (string) ($contract['contract_type'] ?? ''),
                !empty($employee['branch_id']) ? (int) $employee['branch_id'] : null
            );
        } else {
            $empSalary = (new EmployeeSalary())->activeWithStructureForDate($empId, (string)($contract['start_date'] ?? date('Y-m-d')));
        }
        if (!empty($empSalary)) {
            $employee['salary_structure_name'] = (string) ($empSalary['structure_name'] ?? '');
            $employee['grade_level'] = (string) ($empSalary['grade_level'] ?? '');
            $employee['structure_basic_pay'] = (float) ($empSalary['structure_basic_pay'] ?? $empSalary['basic_pay'] ?? 0);
            $employee['basic_pay'] = (float) ($empSalary['basic_pay'] ?? 0);
            $employee['housing_allowance'] = (float) ($empSalary['housing_allowance'] ?? 0);
            $employee['transport_allowance'] = (float) ($empSalary['transport_allowance'] ?? 0);
            $employee['other_allowances'] = (float) ($empSalary['other_allowances'] ?? 0);
        }
        if ($template) {
            $tokenValues = $tmplModel->buildTokenValues($contract, $employee);
            $missingFields = $tmplModel->missingFields((string) $template['body'], $tokenValues);
            $renderedBody = $tmplModel->renderBody((string) $template['body'], $tokenValues);
        }

        $this->renderAuth('contracts/download', [
            'contract' => $contract,
            'employee' => $employee,
            'downloadName' => 'contract-' . (string) ($contract['contract_number'] ?? $contractId),
            'renderedBody' => $renderedBody,
            'missingFields' => $missingFields,
        ]);
    }

    public function leave(): void
    {
        require_employee_auth();
        $emp    = current_employee();
        $empId  = (int) $emp['id'];
        $year   = (int) date('Y');

        $leaveTypes = $this->activeLeaveTypesForPortal();

        $balances = [];
        $leaveModel = new LeaveRequest();
        foreach ($leaveTypes as $lt) {
            $b = $leaveModel->getBalance($empId, (int) $lt['id'], $year);
            $balances[(int) $lt['id']] = $b;
        }

        $requests = $leaveModel->forEmployee($empId);

        $this->renderPortal('portal/leave', [
            'emp'          => $emp,
            'leaveTypes'   => $leaveTypes,
            'balances'     => $balances,
            'requests'     => $requests,
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function leaveApply(): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('portal/leave');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('portal/leave');
        }

        $emp   = current_employee();
        $empId = (int) $emp['id'];

        $leaveTypeId = (int)   $this->input('leave_type_id', 0);
        $startDate   = trim((string) $this->input('start_date', ''));
        $endDate     = trim((string) $this->input('end_date', ''));
        $reason      = trim((string) $this->input('reason', ''));

        if (!$leaveTypeId || !$startDate || !$endDate) {
            Session::flash('error', 'Leave type, start date and end date are required.');
            redirect('portal/leave');
        }

        $start = strtotime($startDate);
        $end   = strtotime($endDate);

        if ($end < $start) {
            Session::flash('error', 'End date must be on or after start date.');
            redirect('portal/leave');
        }

        $days = (int) (($end - $start) / 86400) + 1;

        $model = new LeaveRequest();

        $balance  = $model->getBalance($empId, $leaveTypeId, (int) date('Y', $start));
        $entitled = (float) $balance['entitled_days'];
        $used     = (float) $balance['used_days'];
        $remaining = max(0.0, $entitled - $used);

        if ($days > $remaining) {
            Session::flash('error', "Insufficient leave balance. You have {$remaining} day(s) remaining for this leave type.");
            redirect('portal/leave');
        }

        $model->insert([
            'employee_id'   => $empId,
            'leave_type_id' => $leaveTypeId,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'total_days'    => $days,
            'reason'        => $reason,
            'status'        => 'Pending',
        ]);

        AuditLog::record('portal_leave_apply', "Employee #{$empId} submitted a leave request ({$days} day(s) from {$startDate}).", 'LeaveRequest', null, 'employee');
        Session::flash('success', "Leave request submitted for {$days} day(s). Awaiting HR approval.");
        redirect('portal/leave');
    }

    public function leaveCancelPortal(string $id = '0'): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('portal/leave');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('portal/leave');
        }

        $empId = (int) current_employee()['id'];
        $model = new LeaveRequest();
        $req   = $model->find((int) $id);

        if (!$req || (int) $req['employee_id'] !== $empId) {
            Session::flash('error', 'Leave request not found.');
            redirect('portal/leave');
        }

        if ($req['status'] !== 'Pending') {
            Session::flash('error', 'Only pending requests can be cancelled.');
            redirect('portal/leave');
        }

        $model->update((int) $id, ['status' => 'Cancelled']);
        AuditLog::record('portal_leave_cancel', "Employee #{$empId} cancelled leave request #{$id}.", 'LeaveRequest', (int) $id, 'employee');
        Session::flash('success', 'Leave request cancelled.');
        redirect('portal/leave');
    }

    public function changePassword(): void
    {
        require_employee_auth();
        $this->renderPortal('portal/change-password', [
            'emp'        => current_employee(),
            'forceChange' => !empty(current_employee()['portal_must_change_password']),
            'csrf'       => Session::csrfToken(),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function changePasswordStore(): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('portal/changePassword');
        }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('portal/changePassword');
        }

        $empId   = (int) (current_employee()['id']);
        $forceChange = !empty(current_employee()['portal_must_change_password']);
        $current = (string) $this->input('current_password', '');
        $new     = (string) $this->input('new_password', '');
        $confirm = (string) $this->input('confirm_password', '');

        $stmt = db()->prepare("SELECT portal_password_hash, portal_must_change_password FROM employees WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $empId]);
        $row = $stmt->fetch();

        if (!$row) {
            Session::flash('error', 'Employee account not found.');
            redirect('portal/changePassword');
        }

        if (!$forceChange && !password_verify($current, (string) ($row['portal_password_hash'] ?? ''))) {
            Session::flash('error', 'Current password is incorrect.');
            redirect('portal/changePassword');
        }

        $passwordError = $this->passwordPolicyError($new);
        if ($passwordError !== null) {
            Session::flash('error', $passwordError);
            redirect('portal/changePassword');
        }

        if ($new !== $confirm) {
            Session::flash('error', 'New passwords do not match.');
            redirect('portal/changePassword');
        }

        db()->prepare("UPDATE employees
                       SET portal_password_hash = :h,
                           portal_must_change_password = 0,
                           portal_password_set_at = NOW(),
                           portal_password_expires_at = NULL
                       WHERE id = :id")
           ->execute(['h' => password_hash($new, PASSWORD_DEFAULT), 'id' => $empId]);

        $_SESSION['emp_user']['portal_must_change_password'] = 0;
        Session::flash('success', 'Password updated successfully.');
        redirect('portal/dashboard');
    }

    public function profile(): void
    {
        require_employee_auth();
        $empId = (int) (current_employee()['id']);

        $stmt = db()->prepare(
            "SELECT e.*, d.name AS department_name, g.name AS gender_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN genders     g ON g.id = e.gender_id
             WHERE e.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $empId]);
        $employee = $stmt->fetch();

        if (!$employee) { redirect('portal/dashboard'); }

        $genders = db()->query("SELECT * FROM genders ORDER BY name ASC")->fetchAll();
        $profileRequests = (new EmployeeProfileChangeRequest())->forEmployee($empId);

        $this->renderPortal('portal/profile', [
            'emp'          => current_employee(),
            'employee'     => $employee,
            'genders'      => $genders,
            'profileRequests' => $profileRequests,
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
            'profileCompletion' => $this->profileCompletion($empId),
        ]);
    }

    public function profileUpdate(): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('portal/profile'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('portal/profile');
        }

        $empId = (int) current_employee()['id'];
        $beforeStmt = db()->prepare('SELECT * FROM employees WHERE id = :id LIMIT 1');
        $beforeStmt->execute(['id' => $empId]);
        $rowBefore = $beforeStmt->fetch() ?: [];

        $genderId = (int) $this->input('gender_id', 0) ?: null;
        $dob      = trim((string) $this->input('date_of_birth', '')) ?: null;

        $data = [
            'phone'               => trim((string) $this->input('phone', '')),
            'gender_id'           => $genderId,
            'date_of_birth'       => $dob,
            'nrc_number'          => trim((string) $this->input('nrc_number', '')),
            'napsa_number'        => trim((string) $this->input('napsa_number', '')),
            'tpin'                => trim((string) $this->input('tpin', '')),
            'bank_name'           => trim((string) $this->input('bank_name', '')),
            'bank_account_number' => trim((string) $this->input('bank_account_number', '')),
            'address'             => trim((string) $this->input('address', '')),
        ];

        $changes = [];
        foreach ($data as $field => $newValue) {
            $oldValue = $rowBefore[$field] ?? null;
            if ((string) ($oldValue ?? '') !== (string) ($newValue ?? '')) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        if ($changes === []) {
            Session::flash('success', 'No profile changes were submitted.');
            redirect('portal/profile');
        }

        try {
            $requestId = (new EmployeeProfileChangeRequest())->createForChanges($empId, $changes);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('portal/profile');
        }

        AuditLog::record('portal_profile_change_request', "Employee #{$empId} submitted profile changes for HR approval.", 'EmployeeProfileChangeRequest', $requestId, 'employee', $changes);
        Session::flash('success', 'Profile change request submitted. HR will review it before your record is updated.');
        redirect('portal/profile');
    }

    public function salaryAdvance(): void
    {
        require_employee_auth();
        $emp   = current_employee();
        $empId = (int) $emp['id'];

        $model         = new SalaryAdvance();
        $advances      = $model->forEmployee($empId);
        $activeAdvance = null;
        $pendingAdvance = null;
        foreach ($advances as $a) {
            if ($a['status'] === 'Active')  $activeAdvance  = $a;
            if ($a['status'] === 'Pending') $pendingAdvance = $a;
        }

        $this->renderPortal('portal/salary-advance', [
            'emp'            => $emp,
            'advances'       => $advances,
            'activeAdvance'  => $activeAdvance,
            'pendingAdvance' => $pendingAdvance,
            'csrf'           => Session::csrfToken(),
            'flashSuccess'   => Session::flash('success'),
            'flashError'     => Session::flash('error'),
        ]);
    }

    public function salaryAdvanceApply(): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('portal/salaryAdvance'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('portal/salaryAdvance');
        }

        $empId  = (int) current_employee()['id'];
        $model  = new SalaryAdvance();

        $existing = $model->activeForEmployee($empId);
        if ($existing) {
            Session::flash('error', 'You already have an active salary advance.');
            redirect('portal/salaryAdvance');
        }

        $stmt = db()->prepare("SELECT id FROM salary_advances WHERE employee_id=:eid AND status='Pending' LIMIT 1");
        $stmt->execute(['eid' => $empId]);
        if ($stmt->fetch()) {
            Session::flash('error', 'You already have a pending advance request awaiting approval.');
            redirect('portal/salaryAdvance');
        }

        $amount    = (float) $this->input('amount', 0);
        $monthly   = (float) $this->input('monthly_deduction', 0);
        $startDate = trim((string) $this->input('start_date', date('Y-m-d')));
        $reason    = trim((string) $this->input('reason', ''));

        if ($amount <= 0)   { Session::flash('error', 'Amount must be greater than zero.'); redirect('portal/salaryAdvance'); }
        if ($monthly <= 0 || $monthly > $amount) { Session::flash('error', 'Monthly deduction must be between 1 and the advance amount.'); redirect('portal/salaryAdvance'); }

        $model->insert([
            'employee_id'         => $empId,
            'amount'              => $amount,
            'monthly_deduction'   => $monthly,
            'outstanding_balance' => $amount,
            'start_date'          => $startDate,
            'reason'              => $reason,
            'status'              => 'Pending',
        ]);

        AuditLog::record('portal_advance_apply', "Employee #{$empId} requested a salary advance of ZMW {$amount}.", 'SalaryAdvance', null, 'employee', [
            'amount' => $amount,
            'monthly_deduction' => $monthly,
            'start_date' => $startDate,
        ]);
        Session::flash('success', "Advance request of ZMW " . number_format($amount, 2) . " submitted. Awaiting Finance approval.");
        redirect('portal/salaryAdvance');
    }

    public function documents(): void
    {
        require_employee_auth();
        $emp   = current_employee();
        $empId = (int) $emp['id'];

        $docs = (new EmployeeDocument())->forEmployee($empId);
        $letters = (new EmployeeGeneratedLetter())->forEmployee($empId);
        $contracts = (new EmployeeContract())->listForEmployee($empId);

        $this->renderPortal('portal/documents', [
            'emp'          => $emp,
            'documents'    => $docs,
            'generatedLetters' => $letters,
            'contracts' => $contracts,
            'requiredTypes' => $this->requiredDocumentTypes(),
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function documentUpload(): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('portal/documents'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('portal/documents');
        }

        $empId = (int) current_employee()['id'];
        $file  = $_FILES['doc_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'No file uploaded or upload error.');
            redirect('portal/documents');
        }

        try {
            $mime = UploadedFileGuard::validate($file, UploadedFileGuard::DOCUMENT_MIMES, 5 * 1024 * 1024);
            $safeName = UploadedFileGuard::safeStoredName('emp_' . $empId, $mime, UploadedFileGuard::DOCUMENT_MIMES);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('portal/documents');
        }

        $dir      = BASE_PATH . '/uploads/employee_docs/';
        if (!is_dir($dir)) { mkdir($dir, 0755, true); }
        $dest = $dir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Session::flash('error', 'Failed to save file. Please try again.');
            redirect('portal/documents');
        }

        (new EmployeeDocument())->insert([
            'employee_id'         => $empId,
            'document_type'       => trim((string) $this->input('document_type', 'Other')),
            'file_name'           => $file['name'],
            'file_path'           => 'uploads/employee_docs/' . $safeName,
            'file_size'           => (int) $file['size'],
            'mime_type'           => $mime,
            'notes'               => trim((string) $this->input('notes', '')),
            'uploaded_by_employee'=> 1,
        ]);

        AuditLog::record('portal_doc_upload', "Employee #{$empId} uploaded document: {$file['name']}.", 'EmployeeDocument', null, 'employee', [
            'document_type' => trim((string) $this->input('document_type', 'Other')),
            'file_size' => (int) $file['size'],
            'mime_type' => $mime,
        ]);
        Session::flash('success', 'Document uploaded successfully.');
        redirect('portal/documents');
    }

    public function documentDownload(string $id = '0'): void
    {
        require_employee_auth();
        $empId = (int) current_employee()['id'];
        $doc   = (new EmployeeDocument())->find((int) $id);

        if (!$doc || (int) $doc['employee_id'] !== $empId) {
            Session::flash('error', 'Document not found.');
            redirect('portal/documents');
        }

        $path = BASE_PATH . '/' . ltrim((string)$doc['file_path'], '/');
        if (!is_file($path)) {
            Session::flash('error', 'File not found on server.');
            redirect('portal/documents');
        }

        $downloadName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($doc['file_name'] ?? 'document'));
        $downloadName = trim((string) $downloadName, '._');
        if ($downloadName === '') {
            $downloadName = 'document';
        }

        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function letterView(string $id = '0'): void
    {
        require_employee_auth();
        $empId = (int) current_employee()['id'];
        $letter = (new EmployeeGeneratedLetter())->find((int) $id);
        if (!$letter || (int) ($letter['employee_id'] ?? 0) !== $empId) {
            Session::flash('error', 'Letter not found.');
            redirect('portal/documents');
        }

        $this->renderAuth('employees/letter', [
            'letter' => $letter,
            'title' => (string) ($letter['title'] ?? 'Employee Letter'),
        ]);
    }

    public function documentDelete(string $id = '0'): void
    {
        require_employee_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('portal/documents'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('portal/documents');
        }

        $empId = (int) current_employee()['id'];
        $model = new EmployeeDocument();
        $doc   = $model->find((int) $id);

        if (!$doc || (int) $doc['employee_id'] !== $empId) {
            Session::flash('error', 'Document not found.');
            redirect('portal/documents');
        }

        $path = BASE_PATH . '/' . ltrim((string)$doc['file_path'], '/');
        if (is_file($path)) { @unlink($path); }

        $model->delete((int) $id);
        AuditLog::record('portal_doc_delete', "Employee #{$empId} deleted document #{$id}.", 'EmployeeDocument', (int) $id, 'employee');
        Session::flash('success', 'Document deleted.');
        redirect('portal/documents');
    }

    public function announcements(): void
    {
        require_employee_auth();
        $items = (new Announcement())->published();

        $this->renderPortal('portal/announcements', [
            'emp'           => current_employee(),
            'announcements' => $items,
        ]);
    }

    public function lifecycle(): void
    {
        require_employee_auth();
        $empId = (int) current_employee()['id'];

        $employeeStmt = db()->prepare(
            "SELECT e.*, d.name AS department_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE e.id = :id LIMIT 1"
        );
        $employeeStmt->execute(['id' => $empId]);
        $employee = $employeeStmt->fetch() ?: current_employee();

        $this->renderPortal('portal/lifecycle', [
            'emp' => current_employee(),
            'employee' => $employee,
            'events' => $this->tableExists('employee_lifecycle_events') ? (new EmployeeLifecycle())->forEmployee($empId) : [],
            'contracts' => $this->tableExists('employee_contracts') ? (new EmployeeContract())->listForEmployee($empId) : [],
            'profileRequests' => (new EmployeeProfileChangeRequest())->forEmployee($empId),
        ]);
    }

    public function notifications(): void
    {
        require_employee_auth();
        $empId = (int) current_employee()['id'];

        $this->renderPortal('portal/notifications', [
            'emp' => current_employee(),
            'notifications' => $this->employeeNotifications($empId),
        ]);
    }

    private function employeeNotifications(int $empId): array
    {
        $items = [];

        if ($this->tableExists('payroll_items') && $this->tableExists('payroll_runs')) {
            try {
                $payStmt = db()->prepare(
                    "SELECT pr.pay_period, pr.payslips_released_at, pi.id
                     FROM payroll_items pi
                     JOIN payroll_runs pr ON pr.id = pi.payroll_run_id
                     WHERE pi.employee_id = :eid AND pr.payslips_released = 1
                     ORDER BY COALESCE(pr.payslips_released_at, pr.updated_at, pr.created_at) DESC
                     LIMIT 5"
                );
                $payStmt->execute(['eid' => $empId]);
                foreach ($payStmt->fetchAll() as $row) {
                    $items[] = [
                        'type' => 'success',
                        'title' => 'Payslip released',
                        'message' => 'Your payslip for ' . (string) $row['pay_period'] . ' is available.',
                        'date' => (string) ($row['payslips_released_at'] ?? ''),
                        'link' => base_url('portal/payslipView/' . (string) $row['id']),
                    ];
                }
            } catch (Throwable) {
                // Older deployments may not have all payroll release columns yet.
            }
        }

        if ($this->tableExists('leave_requests') && $this->tableExists('leave_types')) {
            try {
                $leaveStmt = db()->prepare(
                    "SELECT lr.*, lt.name AS leave_type_name
                     FROM leave_requests lr
                     JOIN leave_types lt ON lt.id = lr.leave_type_id
                     WHERE lr.employee_id = :eid
                     ORDER BY lr.updated_at DESC, lr.id DESC
                     LIMIT 5"
                );
                $leaveStmt->execute(['eid' => $empId]);
                foreach ($leaveStmt->fetchAll() as $row) {
                    $items[] = [
                        'type' => (string) $row['status'] === 'Approved' ? 'success' : ((string) $row['status'] === 'Rejected' ? 'danger' : 'info'),
                        'title' => 'Leave ' . strtolower((string) $row['status']),
                        'message' => (string) $row['leave_type_name'] . ' request from ' . (string) $row['start_date'] . ' to ' . (string) $row['end_date'] . '.',
                        'date' => (string) ($row['updated_at'] ?? $row['created_at'] ?? ''),
                        'link' => base_url('portal/leave'),
                    ];
                }
            } catch (Throwable) {
                // Leave notification data is helpful but should not block the portal.
            }
        }

        if ($this->tableExists('employee_contracts')) {
            try {
                $contractStmt = db()->prepare(
                    "SELECT *
                     FROM employee_contracts
                     WHERE employee_id = :eid AND status = 'Active' AND approval_status = 'Approved' AND end_date IS NOT NULL
                     ORDER BY end_date ASC LIMIT 3"
                );
                $contractStmt->execute(['eid' => $empId]);
                foreach ($contractStmt->fetchAll() as $row) {
                    $days = (int) floor((strtotime((string) $row['end_date']) - strtotime(date('Y-m-d'))) / 86400);
                    if ($days <= 60) {
                        $items[] = [
                            'type' => $days <= 30 ? 'warning' : 'info',
                            'title' => 'Contract expiry reminder',
                            'message' => $days >= 0 ? 'Your contract expires in ' . $days . ' day(s).' : 'Your contract has reached its end date.',
                            'date' => (string) $row['end_date'],
                            'link' => base_url('portal/contract'),
                        ];
                    }
                }
            } catch (Throwable) {
                // Contract reminders are optional on older schemas.
            }
        }

        foreach ((new EmployeeProfileChangeRequest())->forEmployee($empId) as $request) {
            $items[] = [
                'type' => (string) $request['status'] === 'Approved' ? 'success' : ((string) $request['status'] === 'Rejected' ? 'danger' : 'info'),
                'title' => 'Profile change ' . strtolower((string) $request['status']),
                'message' => 'Your profile change request is ' . strtolower((string) $request['status']) . '.',
                'date' => (string) ($request['reviewed_at'] ?? $request['created_at'] ?? ''),
                'link' => base_url('portal/profile'),
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
        return array_slice($items, 0, 20);
    }

    private function activeLeaveTypesForPortal(): array
    {
        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = db()->prepare("SELECT * FROM leave_types WHERE is_active = 1$and ORDER BY name ASC");
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);

        return $stmt->fetchAll();
    }

    private function profileCompletion(int $empId): array
    {
        $stmt = db()->prepare('SELECT * FROM employees WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $empId]);
        $employee = $stmt->fetch() ?: [];

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

        $docs = (new EmployeeDocument())->forEmployee($empId);
        $uploadedTypes = array_map(static fn(array $doc): string => (string) ($doc['document_type'] ?? ''), $docs);
        foreach ($this->requiredDocumentTypes() as $type) {
            if (!in_array($type, $uploadedTypes, true)) {
                $missing[] = $type . ' document';
            }
        }

        $total = count($fields) + count($this->requiredDocumentTypes());
        $done = max(0, $total - count($missing));

        return [
            'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 100,
            'missing' => $missing,
            'done' => $done,
            'total' => $total,
        ];
    }

    private function requiredDocumentTypes(): array
    {
        return ['NRC / National ID', 'Bank Statement'];
    }

    private function passwordPolicyError(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'New password must be at least 10 characters.';
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            return 'New password must include uppercase, lowercase, and a number.';
        }

        return null;
    }

    private function employeeNapsaContributionTotals(int $empId): array
    {
        $totals = [
            'employee' => 0.0,
            'employer' => 0.0,
            'total' => 0.0,
        ];

        if (!$this->tableExists('payroll_item_deductions')) {
            return $totals;
        }

        try {
            $stmt = db()->prepare(
                "SELECT deduction_category, COALESCE(SUM(amount), 0) AS amount
                 FROM payroll_item_deductions
                 WHERE employee_id = :employee_id
                   AND UPPER(deduction_code) = 'NAPSA'
                   AND deduction_category IN ('statutory_employee', 'statutory_employer')
                 GROUP BY deduction_category"
            );
            $stmt->execute(['employee_id' => $empId]);
            foreach ($stmt->fetchAll() as $row) {
                $category = (string) ($row['deduction_category'] ?? '');
                if ($category === 'statutory_employee') {
                    $totals['employee'] = (float) ($row['amount'] ?? 0);
                }
                if ($category === 'statutory_employer') {
                    $totals['employer'] = (float) ($row['amount'] ?? 0);
                }
            }
        } catch (Throwable) {
            return $totals;
        }

        $totals['total'] = $totals['employee'] + $totals['employer'];
        return $totals;
    }

    private function contractExpirySummary(?array $contract): array
    {
        if (!$contract || empty($contract['end_date'])) {
            return [
                'has_end_date' => false,
                'days' => null,
                'label' => 'Open-ended',
                'status' => 'No fixed expiry',
            ];
        }

        $today = new DateTimeImmutable('today');
        $end = date_create_immutable((string) $contract['end_date']);
        if (!$end) {
            return [
                'has_end_date' => false,
                'days' => null,
                'label' => 'Not set',
                'status' => 'No fixed expiry',
            ];
        }

        $days = (int) $today->diff($end)->format('%r%a');
        return [
            'has_end_date' => true,
            'days' => $days,
            'label' => $days >= 0 ? (string) $days : (string) abs($days),
            'status' => $days >= 0 ? 'days remaining' : 'days overdue',
            'end_date' => $end->format('Y-m-d'),
        ];
    }

    private function configuredGratuityPolicy(): array
    {
        $settings = new Setting();
        $rate = max(0.0, min(100.0, $settings->numericValue('gratuity_rate_percent', 5.0)));
        $qualifyingYears = max(0.0, min(50.0, $settings->numericValue('gratuity_qualifying_years', 2.0)));
        $basis = $settings->value('gratuity_basis', 'annual_basic_earned');
        $paymentTiming = $settings->value('gratuity_payment_timing', 'End of contract');

        if (!in_array($basis, ['annual_basic_earned', 'monthly_basic_served'], true)) {
            $basis = 'annual_basic_earned';
        }

        return [
            'rate' => $rate,
            'qualifying_years' => $qualifyingYears,
            'basis' => $basis,
            'payment_timing' => $paymentTiming !== '' ? $paymentTiming : 'End of contract',
        ];
    }

    private function estimatePortalGratuity(?array $contract, ?array $salary, array $policy): array
    {
        $rate = (float) ($policy['rate'] ?? 5.0);
        $qualifyingYears = (float) ($policy['qualifying_years'] ?? 2.0);
        $basis = (string) ($policy['basis'] ?? 'annual_basic_earned');

        $empty = [
            'eligible' => false,
            'amount' => 0.0,
            'accrued_amount' => 0.0,
            'months' => 0,
            'years' => 0.0,
            'rate' => $rate,
            'qualifying_years' => $qualifyingYears,
            'payment_timing' => (string) ($policy['payment_timing'] ?? 'End of contract'),
        ];

        if (!$contract || !$salary) {
            return $empty;
        }

        $start = date_create((string) ($contract['start_date'] ?? ''));
        if (!$start) {
            return $empty;
        }

        $today = new DateTimeImmutable('today');
        $endDate = !empty($contract['end_date']) ? date_create_immutable((string) $contract['end_date']) : null;
        $asOf = $endDate && $endDate < $today ? $endDate : $today;
        $diff = $start->diff($asOf);
        $months = max(0, ((int) $diff->y * 12) + (int) $diff->m);
        $years = $months / 12;
        $basicPay = (float) ($salary['basic_pay'] ?? 0);
        $annualBasicEarned = $basis === 'monthly_basic_served'
            ? $basicPay * $months
            : $basicPay * 12 * $years;
        $accruedAmount = round($annualBasicEarned * ($rate / 100), 2);
        $eligible = $years + 0.0001 >= $qualifyingYears;

        return array_merge($empty, [
            'eligible' => $eligible,
            'amount' => $eligible ? $accruedAmount : 0.0,
            'accrued_amount' => $accruedAmount,
            'months' => $months,
            'years' => $years,
        ]);
    }

    private function clientIp(): string
    {
        $forwarded = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        if ($forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $time = strtotime($value);
        return $time !== false ? date('Y-m-d', $time) : null;
    }

    private function tableExists(string $table): bool
    {
        static $cache = [];
        if (array_key_exists($table, $cache)) {
            return $cache[$table];
        }

        try {
            $stmt = db()->prepare(
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
}

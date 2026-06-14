<?php

declare(strict_types=1);

class SalaryAdvanceController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer', 'Viewer']);

        $model    = new SalaryAdvance();
        $advances = $model->listWithDetails();

        $this->render('salary-advance/index', [
            'title'        => 'Salary Advances',
            'advances'     => $advances,
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        $cid = Tenant::id();
        $and = $cid > 0 ? ' AND company_id = :cid' : '';
        $stmt = db()->prepare("SELECT id, full_name, employee_number FROM employees WHERE contract_status='Active'$and ORDER BY full_name ASC");
        $stmt->execute($cid > 0 ? ['cid' => $cid] : []);
        $employees = $stmt->fetchAll();

        $this->render('salary-advance/create', [
            'title'      => 'New Salary Advance',
            'employees'  => $employees,
            'csrf'       => Session::csrfToken(),
            'flashError' => Session::flash('error'),
            'old'        => $_SESSION['_old_advance'] ?? [],
        ]);
        unset($_SESSION['_old_advance']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('salary-advance/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('salary-advance/create');
        }

        $user = current_user();
        $data = [
            'employee_id'         => (int) $this->input('employee_id', 0),
            'amount'              => (float) $this->input('amount', 0),
            'monthly_deduction'   => (float) $this->input('monthly_deduction', 0),
            'outstanding_balance' => (float) $this->input('amount', 0),
            'start_date'          => (string) $this->input('start_date', date('Y-m-d')),
            'reason'              => trim((string) $this->input('reason', '')),
            'status'              => 'Pending',
            'created_by'          => (int) ($user['id'] ?? 0) ?: null,
            'approved_by'         => null,
        ];
        $_SESSION['_old_advance'] = $data;

        if (!$data['employee_id']) {
            Session::flash('error', 'Employee is required.');
            redirect('salary-advance/create');
        }
        if ($data['amount'] <= 0) {
            Session::flash('error', 'Advance amount must be greater than zero.');
            redirect('salary-advance/create');
        }
        if ($data['monthly_deduction'] <= 0 || $data['monthly_deduction'] > $data['amount']) {
            Session::flash('error', 'Monthly deduction must be between 0 and the advance amount.');
            redirect('salary-advance/create');
        }

        $existing = (new SalaryAdvance())->activeForEmployee($data['employee_id']);
        if ($existing) {
            Session::flash('error', 'This employee already has an active salary advance. Clear the outstanding balance first.');
            redirect('salary-advance/create');
        }

        $model = new SalaryAdvance();
        $advanceId = $model->insert($data);
        WorkflowEvent::record('salary_advance', 'SalaryAdvance', $advanceId, null, 'Pending', 'advance_request', 'Salary advance submitted for approval.');
        AuditLog::record('advance_create', "Salary advance of ZMW {$data['amount']} requested for employee #{$data['employee_id']}.", 'SalaryAdvance', $advanceId);
        unset($_SESSION['_old_advance']);
        Session::flash('success', 'Salary advance submitted for approval.');
        redirect('salary-advance/index');
    }

    public function approve(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('salary-advance/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('salary-advance/index');
        }

        $advanceId = (int) $id;
        $model = new SalaryAdvance();
        $advance = $model->find($advanceId);

        if (!$advance || (string) ($advance['status'] ?? '') !== 'Pending') {
            Session::flash('error', 'Advance not found or already actioned.');
            redirect('salary-advance/index');
        }

        $workflow = new WorkflowDefinition();
        $requiredRole = $workflow->requiredRoleFor('salary_advance', 1, 'Finance Officer');
        if (!$this->userMatchesWorkflowRole($requiredRole)) {
            Session::flash('error', "This workflow step requires {$requiredRole} approval.");
            redirect('salary-advance/index');
        }

        $action = (string) $this->input('action', 'approve');
        if ($action === 'reject') {
            $model->update($advanceId, ['status' => 'Cancelled']);
            WorkflowEvent::record('salary_advance', 'SalaryAdvance', $advanceId, 'Pending', 'Cancelled', 'advance_reject', trim((string) $this->input('reason', '')));
            AuditLog::record('advance_reject', "Salary advance #{$advanceId} rejected.", 'SalaryAdvance', $advanceId);
            Session::flash('success', 'Salary advance rejected.');
            redirect('salary-advance/index');
        }

        $model->update($advanceId, [
            'status' => 'Active',
            'approved_by' => (int) (current_user()['id'] ?? 0) ?: null,
        ]);
        WorkflowEvent::record('salary_advance', 'SalaryAdvance', $advanceId, 'Pending', 'Active', 'advance_approve', $workflow->actionLabelFor('salary_advance', 1, 'Approve Advance'));
        AuditLog::record('advance_approve', "Salary advance #{$advanceId} approved.", 'SalaryAdvance', $advanceId);
        Session::flash('success', 'Salary advance approved and activated.');
        redirect('salary-advance/index');
    }

    public function cancel(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('salary-advance/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('salary-advance/index');
        }

        $model   = new SalaryAdvance();
        $advance = $model->find((int) $id);

        if (!$advance) {
            Session::flash('error', 'Advance not found.');
            redirect('salary-advance/index');
        }

        $model->update((int) $id, ['status' => 'Cancelled']);
        AuditLog::record('advance_cancel', "Salary advance #{$id} cancelled.", 'SalaryAdvance', (int) $id);
        Session::flash('success', 'Advance cancelled.');
        redirect('salary-advance/index');
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
}

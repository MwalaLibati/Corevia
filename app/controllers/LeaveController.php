<?php

declare(strict_types=1);

class LeaveController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $model    = new LeaveRequest();
        $tab      = (string) $this->input('tab', 'all');
        $requests = $tab === 'pending' ? $model->pending() : $model->listWithDetails();

        $pendingCount = count($model->pending());

        $this->render('leave/index', [
            'title'        => 'Leave Management',
            'requests'     => $requests,
            'tab'          => $tab,
            'pendingCount' => $pendingCount,
            'csrf'         => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function create(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        $cid = Tenant::id();
        $empSql = "SELECT id, full_name, employee_number FROM employees WHERE contract_status='Active'"
                . ($cid > 0 ? ' AND company_id = :cid' : '')
                . ' ORDER BY full_name ASC';
        $empStmt = db()->prepare($empSql);
        $empStmt->execute($cid > 0 ? ['cid' => $cid] : []);
        $employees = $empStmt->fetchAll();
        $leaveTypes = (new LeaveType())->active();

        $this->render('leave/create', [
            'title'       => 'New Leave Request',
            'employees'   => $employees,
            'leaveTypes'  => $leaveTypes,
            'csrf'        => Session::csrfToken(),
            'flashError'  => Session::flash('error'),
            'old'         => $_SESSION['_old_leave'] ?? [],
        ]);
        unset($_SESSION['_old_leave']);
    }

    public function store(): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('leave/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('leave/create');
        }

        $data = [
            'employee_id'   => (int) $this->input('employee_id', 0),
            'leave_type_id' => (int) $this->input('leave_type_id', 0),
            'start_date'    => (string) $this->input('start_date', ''),
            'end_date'      => (string) $this->input('end_date', ''),
            'reason'        => trim((string) $this->input('reason', '')),
        ];
        $_SESSION['_old_leave'] = $data;

        if (!$data['employee_id'] || !$data['leave_type_id'] || !$data['start_date'] || !$data['end_date']) {
            Session::flash('error', 'Employee, leave type, start date and end date are required.');
            redirect('leave/create');
        }

        $start = strtotime($data['start_date']);
        $end   = strtotime($data['end_date']);
        if ($end < $start) {
            Session::flash('error', 'End date must be on or after start date.');
            redirect('leave/create');
        }

        $days = (int) (($end - $start) / 86400) + 1;
        $data['total_days'] = $days;
        $data['status']     = 'Pending';

        $model = new LeaveRequest();
        $id = $model->insert($data);
        WorkflowEvent::record('leave', 'LeaveRequest', $id, null, 'Pending', 'leave_submit', 'Leave request submitted for HR approval.');
        AuditLog::record('leave_create', "Leave request created for employee #{$data['employee_id']} ({$days} days, type #{$data['leave_type_id']}).", 'LeaveRequest');
        unset($_SESSION['_old_leave']);
        Session::flash('success', 'Leave request submitted successfully.');
        redirect('leave/index');
    }

    public function view(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer', 'Finance Officer', 'Viewer']);

        $model   = new LeaveRequest();
        $request = $model->findDetailed((int) $id);

        if (!$request) {
            Session::flash('error', 'Leave request not found.');
            redirect('leave/index');
        }

        $balance = $model->getBalance(
            (int) $request['employee_id'],
            (int) $request['leave_type_id'],
            (int) date('Y', strtotime($request['start_date']))
        );

        $this->render('leave/view', [
            'title'   => 'Leave Request',
            'request' => $request,
            'balance' => $balance,
            'csrf'    => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError'   => Session::flash('error'),
        ]);
    }

    public function approve(string $id): void
    {
        require_auth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('leave/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('leave/view/' . $id);
        }

        $model   = new LeaveRequest();
        $request = $model->find((int) $id);

        if (!$request || $request['status'] !== 'Pending') {
            Session::flash('error', 'Request not found or already actioned.');
            redirect('leave/index');
        }

        $action = (string) $this->input('action', '');
        $user   = current_user();
        $workflow = new WorkflowDefinition();
        $requiredRole = $workflow->requiredRoleFor('leave', 1, 'HR Officer');

        if (!$this->userMatchesWorkflowRole($requiredRole)) {
            Session::flash('error', "This workflow step requires {$requiredRole} approval.");
            redirect('leave/view/' . $id);
        }

        if ($action === 'approve') {
            $model->update((int) $id, [
                'status'      => 'Approved',
                'approved_by' => (int) ($user['id'] ?? 0),
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
            WorkflowEvent::record('leave', 'LeaveRequest', (int) $id, 'Pending', 'Approved', 'leave_approve', $workflow->actionLabelFor('leave', 1, 'Approve Leave'));
            $year = (int) date('Y', strtotime((string) $request['start_date']));
            $model->updateBalance((int) $request['employee_id'], (int) $request['leave_type_id'], $year, (float) $request['total_days']);
            AuditLog::record('leave_approve', "Leave request #{$id} approved.", 'LeaveRequest', (int) $id);
            Session::flash('success', 'Leave request approved and balance updated.');
        } elseif ($action === 'reject') {
            $reason = trim((string) $this->input('rejection_reason', ''));
            $model->update((int) $id, [
                'status'           => 'Rejected',
                'rejection_reason' => $reason,
                'approved_by'      => (int) ($user['id'] ?? 0),
            ]);
            WorkflowEvent::record('leave', 'LeaveRequest', (int) $id, 'Pending', 'Rejected', 'leave_reject', $reason);
            AuditLog::record('leave_reject', "Leave request #{$id} rejected.", 'LeaveRequest', (int) $id);
            Session::flash('success', 'Leave request rejected.');
        }

        redirect('leave/index?tab=pending');
    }

    public function cancel(string $id): void
    {
        require_auth();
        require_role(['Super Admin', 'HR Officer']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('leave/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('leave/index');
        }

        $model   = new LeaveRequest();
        $request = $model->find((int) $id);

        if (!$request) {
            Session::flash('error', 'Request not found.');
            redirect('leave/index');
        }

        $model->update((int) $id, ['status' => 'Cancelled']);
        WorkflowEvent::record('leave', 'LeaveRequest', (int) $id, (string) $request['status'], 'Cancelled', 'leave_cancel', 'Leave request cancelled.');
        AuditLog::record('leave_cancel', "Leave request #{$id} cancelled.", 'LeaveRequest', (int) $id);
        Session::flash('success', 'Leave request cancelled.');
        redirect('leave/index');
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

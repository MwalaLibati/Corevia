<?php

declare(strict_types=1);

class ApprovalInboxController extends Controller
{
    public function index(): void
    {
        require_auth();

        $model = new ApprovalInbox();
        $approvalInboxItems = $model->pendingForCurrentUser(200);

        $this->render('approvals/index', [
            'title' => 'My Approvals',
            'approvalInboxItems' => $approvalInboxItems,
            'approvalInboxSummary' => $model->summaryForItems($approvalInboxItems),
        ]);
    }

    public function show(string $type, string $id): void
    {
        require_auth();

        $approvalType = $this->normalizeType($type);
        $approvalId = (int) $id;
        $detail = $this->approvalDetail($approvalType, $approvalId);

        if ($detail === null) {
            Session::flash('error', 'Approval item not found or it is not assigned to your role.');
            redirect('approval-inbox/index');
        }

        $this->render('approvals/show', [
            'title' => 'Approval Review',
            'approvalType' => $approvalType,
            'approvalId' => $approvalId,
            'approvalItem' => $detail['item'],
            'detailRows' => $detail['rows'],
            'detailTables' => $detail['tables'],
            'workflowEvents' => $detail['events'],
            'approveRoute' => $detail['approveRoute'],
            'rejectRoute' => $detail['rejectRoute'],
            'moduleRoute' => $detail['moduleRoute'],
            'canReject' => $detail['canReject'],
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function approve(string $type, string $id): void
    {
        $this->handleAction($type, (int) $id, 'approve');
    }

    public function reject(string $type, string $id): void
    {
        $this->handleAction($type, (int) $id, 'reject');
    }

    private function handleAction(string $type, int $id, string $action): void
    {
        require_auth();

        $approvalType = $this->normalizeType($type);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid approval request.');
            redirect('approval-inbox/show/' . $approvalType . '/' . $id);
        }

        try {
            match ($approvalType) {
                'leave' => $this->actionLeave($id, $action),
                'salary_advance' => $this->actionSalaryAdvance($id, $action),
                'payroll' => $this->actionPayroll($id, $action),
                'contract' => $this->actionContract($id, $action),
                'salary_change' => $this->actionSalaryChange($id, $action),
                'employee_onboarding' => $this->actionOnboarding($id, $action),
                default => throw new RuntimeException('Unsupported approval type.'),
            };
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            redirect('approval-inbox/show/' . $approvalType . '/' . $id);
        }

        redirect('approval-inbox/index');
    }

    private function approvalDetail(string $type, int $id): ?array
    {
        $currentItem = $this->currentInboxItem($type, $id);
        if ($currentItem === null) {
            return null;
        }

        return match ($type) {
            'leave' => $this->leaveDetail($id, $currentItem),
            'salary_advance' => $this->advanceDetail($id, $currentItem),
            'payroll' => $this->payrollDetail($id, $currentItem),
            'contract' => $this->contractDetail($id, $currentItem),
            'employee_onboarding' => $this->onboardingDetail($id, $currentItem),
            'salary_change' => $this->salaryChangeDetail($id, $currentItem),
            default => null,
        };
    }

    private function currentInboxItem(string $type, int $id): ?array
    {
        foreach ((new ApprovalInbox())->pendingForCurrentUser(500) as $item) {
            if ((string) ($item['workflow_type'] ?? '') === $type && (int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }

    private function leaveDetail(int $id, array $item): ?array
    {
        $model = new LeaveRequest();
        $request = $model->findDetailed($id);
        if (!$request) {
            return null;
        }

        $balance = $model->getBalance(
            (int) $request['employee_id'],
            (int) $request['leave_type_id'],
            (int) date('Y', strtotime((string) $request['start_date']))
        );

        return $this->detailPayload($item, [
            ['Employee', $this->employeeLabel($request)],
            ['Department', (string) ($request['department_name'] ?? '-')],
            ['Designation', (string) ($request['designation'] ?? '-')],
            ['Leave Type', (string) ($request['leave_type_name'] ?? '-')],
            ['Leave Dates', (string) $request['start_date'] . ' to ' . (string) $request['end_date']],
            ['Days Requested', number_format((float) $request['total_days'], 1)],
            ['Leave Balance', number_format((float) ($balance['balance'] ?? 0), 1) . ' day(s) remaining'],
            ['Reason', (string) ($request['reason'] ?? '-')],
        ], [], 'LeaveRequest', $id, 'leave/view/' . $id, true);
    }

    private function advanceDetail(int $id, array $item): ?array
    {
        $advance = (new SalaryAdvance())->findDetailed($id);
        if (!$advance) {
            return null;
        }

        return $this->detailPayload($item, [
            ['Employee', $this->employeeLabel($advance)],
            ['Department', (string) ($advance['department_name'] ?? '-')],
            ['Requested Amount', format_currency((float) $advance['amount'])],
            ['Monthly Deduction', format_currency((float) $advance['monthly_deduction'])],
            ['Outstanding Balance', format_currency((float) $advance['outstanding_balance'])],
            ['Start Date', (string) ($advance['start_date'] ?? '-')],
            ['Reason', (string) ($advance['reason'] ?? '-')],
        ], [], 'SalaryAdvance', $id, 'salary-advance/index', true);
    }

    private function payrollDetail(int $id, array $item): ?array
    {
        $model = new PayrollRun();
        $run = $model->findDetailed($id);
        if (!$run) {
            return null;
        }

        $items = $model->itemsForRun($id);
        $previewRows = [];
        foreach (array_slice($items, 0, 10) as $payItem) {
            $previewRows[] = [
                (string) ($payItem['employee_name'] ?? '-'),
                format_currency((float) ($payItem['gross_pay'] ?? 0)),
                format_currency((float) ($payItem['total_deductions'] ?? 0)),
                format_currency((float) ($payItem['net_pay'] ?? 0)),
            ];
        }

        return $this->detailPayload($item, [
            ['Pay Period', (string) ($run['pay_period'] ?? '-')],
            ['Run Date', (string) ($run['run_date'] ?? '-')],
            ['Status', (string) ($run['status'] ?? '-')],
            ['Employees Included', number_format((int) ($run['item_count'] ?? count($items)))],
            ['Total Gross', format_currency((float) ($run['total_gross'] ?? 0))],
            ['Total Deductions', format_currency((float) ($run['total_deductions'] ?? 0))],
            ['Total Net', format_currency((float) ($run['total_net'] ?? 0))],
        ], [
            ['title' => 'Payroll Preview', 'headers' => ['Employee', 'Gross', 'Deductions', 'Net'], 'rows' => $previewRows],
        ], 'PayrollRun', $id, 'payroll/edit/' . $id, false);
    }

    private function contractDetail(int $id, array $item): ?array
    {
        $contract = (new EmployeeContract())->findDetailed($id);
        if (!$contract) {
            return null;
        }

        return $this->detailPayload($item, [
            ['Employee', $this->employeeLabel($contract)],
            ['Department', (string) ($contract['department_name'] ?? '-')],
            ['Contract Number', (string) ($contract['contract_number'] ?? '-')],
            ['Contract Type', (string) ($contract['contract_type'] ?? '-')],
            ['Approval Status', (string) ($contract['approval_status'] ?? '-')],
            ['Start Date', (string) ($contract['start_date'] ?? '-')],
            ['End Date', (string) ($contract['end_date'] ?? 'Open-ended')],
            ['Template', (string) ($contract['template_name'] ?? '-')],
            ['Notes', (string) ($contract['notes'] ?? '-')],
        ], [], 'EmployeeContract', $id, 'contract/index', true);
    }

    private function onboardingDetail(int $id, array $item): ?array
    {
        $model = new EmployeeOnboardingRequest();
        $request = $model->findDetailed($id);
        if (!$request) {
            return null;
        }

        $documents = [];
        foreach ($model->documents($id) as $doc) {
            $documents[] = [
                (string) ($doc['document_type'] ?? 'Document'),
                (string) ($doc['original_name'] ?? '-'),
                !empty($doc['stored_path']) ? '<a href="' . e(asset((string) $doc['stored_path'])) . '" target="_blank" rel="noopener">Open</a>' : '-',
            ];
        }

        $payload = $this->detailPayload($item, [
            ['Candidate / Employee', (string) ($request['invited_full_name'] ?? '-')],
            ['Email', (string) ($request['invited_email'] ?? '-')],
            ['Department', (string) ($request['department_name'] ?? '-')],
            ['Expected Start Date', (string) ($request['expected_start_date'] ?? '-')],
            ['Linked Employee', trim((string) ($request['selected_employee_name'] ?? '') . ' ' . (string) ($request['selected_employee_number'] ?? '')) ?: '-'],
            ['Submitted At', (string) ($request['submitted_at'] ?? '-')],
        ], [
            ['title' => 'Uploaded Documents', 'headers' => ['Type', 'File', 'Action'], 'rows' => $documents],
        ], 'EmployeeOnboardingRequest', $id, 'onboarding/show/' . $id, true);

        $payload['approveRoute'] = 'onboarding/approve/' . $id;
        $payload['rejectRoute'] = 'onboarding/cancel/' . $id;

        return $payload;
    }

    private function salaryChangeDetail(int $id, array $item): ?array
    {
        $request = (new SalaryChangeRequest())->findDetailed($id);
        if (!$request) {
            return null;
        }

        return $this->detailPayload($item, [
            ['Employee', $this->employeeLabel($request)],
            ['Current Stage', (string) ($request['status'] ?? '-')],
            ['Salary Structure', (string) ($request['salary_structure_name'] ?? '-')],
            ['Effective Date', (string) ($request['effective_date'] ?? '-')],
            ['Structure Basic Pay', format_currency((float) ($request['structure_basic_pay'] ?? 0))],
            ['Actual Basic Pay', $request['actual_basic_pay'] !== null ? format_currency((float) $request['actual_basic_pay']) : 'Use structure rate'],
            ['Override Reason', (string) ($request['override_reason'] ?? '-')],
        ], [], 'SalaryChangeRequest', $id, 'salary-change/index', true);
    }

    private function detailPayload(array $item, array $rows, array $tables, string $entityType, int $entityId, string $moduleRoute, bool $canReject): array
    {
        return [
            'item' => $item,
            'rows' => $rows,
            'tables' => $tables,
            'events' => (new WorkflowEvent())->forEntity($entityType, $entityId),
            'approveRoute' => 'approval-inbox/approve/' . (string) $item['workflow_type'] . '/' . $entityId,
            'rejectRoute' => 'approval-inbox/reject/' . (string) $item['workflow_type'] . '/' . $entityId,
            'moduleRoute' => $moduleRoute,
            'canReject' => $canReject,
        ];
    }

    private function actionLeave(int $id, string $action): void
    {
        $model = new LeaveRequest();
        $request = $model->findDetailed($id);
        if (!$request || (string) $request['status'] !== 'Pending') {
            throw new RuntimeException('Leave request not found or already actioned.');
        }

        $workflow = new WorkflowDefinition();
        $requiredRole = $workflow->requiredRoleFor('leave', 1, 'HR Officer');
        $this->guardWorkflowRole($requiredRole);

        if ($action === 'reject') {
            $reason = trim((string) $this->input('reason', ''));
            $model->update($id, ['status' => 'Rejected', 'rejection_reason' => $reason, 'approved_by' => (int) (current_user()['id'] ?? 0)]);
            WorkflowEvent::record('leave', 'LeaveRequest', $id, 'Pending', 'Rejected', 'leave_reject', $reason);
            AuditLog::record('leave_reject', "Leave request #{$id} rejected.", 'LeaveRequest', $id);
            Session::flash('success', 'Leave request rejected.');
            return;
        }

        $model->update($id, ['status' => 'Approved', 'approved_by' => (int) (current_user()['id'] ?? 0), 'approved_at' => date('Y-m-d H:i:s')]);
        $year = (int) date('Y', strtotime((string) $request['start_date']));
        $model->updateBalance((int) $request['employee_id'], (int) $request['leave_type_id'], $year, (float) $request['total_days']);
        WorkflowEvent::record('leave', 'LeaveRequest', $id, 'Pending', 'Approved', 'leave_approve', $workflow->actionLabelFor('leave', 1, 'Approve Leave'));
        AuditLog::record('leave_approve', "Leave request #{$id} approved.", 'LeaveRequest', $id);
        Session::flash('success', 'Leave request approved.');
    }

    private function actionSalaryAdvance(int $id, string $action): void
    {
        $model = new SalaryAdvance();
        $advance = $model->findDetailed($id);
        if (!$advance || (string) $advance['status'] !== 'Pending') {
            throw new RuntimeException('Salary advance not found or already actioned.');
        }

        $workflow = new WorkflowDefinition();
        $requiredRole = $workflow->requiredRoleFor('salary_advance', 1, 'Finance Officer');
        $this->guardWorkflowRole($requiredRole);

        if ($action === 'reject') {
            $reason = trim((string) $this->input('reason', ''));
            $model->update($id, ['status' => 'Cancelled']);
            WorkflowEvent::record('salary_advance', 'SalaryAdvance', $id, 'Pending', 'Cancelled', 'advance_reject', $reason);
            AuditLog::record('advance_reject', "Salary advance #{$id} rejected.", 'SalaryAdvance', $id);
            Session::flash('success', 'Salary advance rejected.');
            return;
        }

        $model->update($id, ['status' => 'Active', 'approved_by' => (int) (current_user()['id'] ?? 0) ?: null]);
        WorkflowEvent::record('salary_advance', 'SalaryAdvance', $id, 'Pending', 'Active', 'advance_approve', $workflow->actionLabelFor('salary_advance', 1, 'Approve Advance'));
        AuditLog::record('advance_approve', "Salary advance #{$id} approved.", 'SalaryAdvance', $id);
        Session::flash('success', 'Salary advance approved.');
    }

    private function actionPayroll(int $id, string $action): void
    {
        if ($action === 'reject') {
            throw new RuntimeException('Payroll approvals use staged approval and correction instead of rejection.');
        }

        $model = new PayrollRun();
        $run = $model->find($id);
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        $transitions = [
            'Draft' => ['next' => 'HR Approved', 'workflow_step' => 1, 'fallback_role' => 'HR Officer', 'field' => 'approved_by_hr'],
            'HR Approved' => ['next' => 'Finance Approved', 'workflow_step' => 2, 'fallback_role' => 'Finance Officer', 'field' => 'approved_by_finance'],
            'Finance Approved' => ['next' => 'Admin Approved', 'workflow_step' => 3, 'fallback_role' => 'Super Admin', 'field' => 'approved_by_admin'],
            'Admin Approved' => ['next' => 'Posted', 'workflow_step' => 4, 'fallback_role' => 'Finance Officer', 'field' => null, 'lock' => true],
        ];

        $current = (string) $run['status'];
        if (!isset($transitions[$current])) {
            throw new RuntimeException('This payroll run is not awaiting approval.');
        }
        if (count($model->itemsForRun($id)) === 0) {
            throw new RuntimeException('Generate payroll items before approval.');
        }

        $workflow = new WorkflowDefinition();
        $transition = $transitions[$current];
        $stepOrder = (int) $transition['workflow_step'];
        $requiredRole = $workflow->requiredRoleFor('payroll', $stepOrder, (string) $transition['fallback_role']);
        $this->guardWorkflowRole($requiredRole);

        $updateData = [
            'pay_period' => $run['pay_period'],
            'run_date' => $run['run_date'],
            'status' => $transition['next'],
            'total_gross' => $run['total_gross'],
            'total_deductions' => $run['total_deductions'],
            'total_net' => $run['total_net'],
            'created_by' => $run['created_by'],
            'approved_by_hr' => $run['approved_by_hr'],
            'approved_by_finance' => $run['approved_by_finance'],
            'approved_by_admin' => $run['approved_by_admin'],
        ];
        if ($transition['field'] !== null) {
            $updateData[(string) $transition['field']] = (int) (current_user()['id'] ?? 0);
        }

        $model->update($id, $updateData);
        if (!empty($transition['lock'])) {
            $model->lockRun($id, (int) (current_user()['id'] ?? 0));
        }
        WorkflowEvent::record('payroll', 'PayrollRun', $id, $current, (string) $transition['next'], 'payroll_approve', $workflow->actionLabelFor('payroll', $stepOrder, 'Approve Payroll'));
        AuditLog::record('payroll_approve', "Approved payroll run #{$id} from {$current} to {$transition['next']}.", 'PayrollRun', $id);
        Session::flash('success', 'Payroll workflow advanced.');
    }

    private function actionContract(int $id, string $action): void
    {
        $status = (new EmployeeContract())->advanceApproval($id, $action, trim((string) $this->input('reason', '')));
        AuditLog::record('contract_approval', "Contract #{$id} moved to {$status}.", 'EmployeeContract', $id);
        Session::flash('success', 'Contract workflow updated: ' . $status . '.');
    }

    private function actionSalaryChange(int $id, string $action): void
    {
        $model = new SalaryChangeRequest();
        $request = $model->findDetailed($id);
        if (!$request) {
            throw new RuntimeException('Salary change request not found.');
        }

        $workflow = new WorkflowDefinition();
        $current = (string) $request['status'];
        $userId = (int) (current_user()['id'] ?? 0) ?: null;

        if ($action === 'reject') {
            $model->update($id, ['status' => 'Rejected', 'rejection_reason' => trim((string) $this->input('reason', ''))]);
            WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $id, $current, 'Rejected', 'salary_change_reject');
            AuditLog::record('salary_change_workflow', 'Salary change request #' . $id . ' rejected.', 'SalaryChangeRequest', $id);
            Session::flash('success', 'Salary change rejected.');
            return;
        }

        if ($current === 'Pending Finance Review') {
            $this->guardWorkflowRole($workflow->requiredRoleFor('salary_change', 1, 'Finance Officer'));
            $model->update($id, ['status' => 'Pending Admin Approval', 'finance_reviewed_by' => $userId]);
            WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $id, $current, 'Pending Admin Approval', 'salary_change_finance_review', $workflow->actionLabelFor('salary_change', 1, 'Review Salary Change'));
        } elseif ($current === 'Pending Admin Approval') {
            $this->guardWorkflowRole($workflow->requiredRoleFor('salary_change', 2, 'Super Admin'));
            (new EmployeeSalary())->assignAndActivate((int) $request['employee_id'], (int) $request['salary_structure_id'], (string) $request['effective_date'], [
                'actual_basic_pay' => $request['actual_basic_pay'] !== null ? (float) $request['actual_basic_pay'] : null,
                'actual_housing_allowance' => $request['actual_housing_allowance'] !== null ? (float) $request['actual_housing_allowance'] : null,
                'actual_transport_allowance' => $request['actual_transport_allowance'] !== null ? (float) $request['actual_transport_allowance'] : null,
                'actual_other_allowances' => $request['actual_other_allowances'] !== null ? (float) $request['actual_other_allowances'] : null,
                'override_reason' => $request['override_reason'] ?? null,
            ]);
            $model->update($id, ['status' => 'Applied', 'admin_approved_by' => $userId]);
            WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $id, $current, 'Applied', 'salary_change_admin_apply', $workflow->actionLabelFor('salary_change', 2, 'Apply Salary Change'));
        } else {
            throw new RuntimeException('This salary change is not awaiting approval.');
        }

        AuditLog::record('salary_change_workflow', 'Salary change request #' . $id . ' advanced.', 'SalaryChangeRequest', $id);
        Session::flash('success', 'Salary change workflow advanced.');
    }

    private function actionOnboarding(int $id, string $action): void
    {
        if ($action === 'reject') {
            (new EmployeeOnboardingRequest())->cancel($id);
            AuditLog::record('onboarding_cancel', 'Cancelled onboarding request.', 'EmployeeOnboardingRequest', $id);
            Session::flash('success', 'Onboarding request cancelled.');
            return;
        }

        Session::flash('success', 'Continue onboarding approval from the detailed onboarding processor.');
        redirect('onboarding/show/' . $id);
    }

    private function guardWorkflowRole(string $requiredRole): void
    {
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        if (
            in_array($role, ['Super Admin', 'Admin'], true)
            || in_array($accessLevel, ['Super Admin', 'Admin'], true)
            || $role === $requiredRole
            || $accessLevel === $requiredRole
        ) {
            return;
        }

        throw new RuntimeException("This workflow step requires {$requiredRole} approval.");
    }

    private function normalizeType(string $type): string
    {
        return str_replace('-', '_', trim($type));
    }

    private function employeeLabel(array $row): string
    {
        $name = (string) ($row['employee_name'] ?? $row['full_name'] ?? '-');
        $number = (string) ($row['employee_number'] ?? '');
        return $number !== '' ? $name . ' (' . $number . ')' : $name;
    }
}

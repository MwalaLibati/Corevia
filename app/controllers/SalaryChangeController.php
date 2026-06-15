<?php

declare(strict_types=1);

class SalaryChangeController extends Controller
{
    public function index(): void
    {
        require_auth();
        require_role(['Super Admin', 'Finance Officer', 'HR Officer']);

        $this->render('salary_changes/index', [
            'title' => 'Salary Change Approvals',
            'requests' => (new SalaryChangeRequest())->listWithDetails(),
            'csrf' => Session::csrfToken(),
            'flashSuccess' => Session::flash('success'),
            'flashError' => Session::flash('error'),
        ]);
    }

    public function approve(string $id): void
    {
        require_auth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('salary-change/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('salary-change/index');
        }

        $requestId = (int) $id;
        $model = new SalaryChangeRequest();
        $request = $model->findDetailed($requestId);
        if (!$request) {
            Session::flash('error', 'Salary change request not found.');
            redirect('salary-change/index');
        }

        $userId = (int) (current_user()['id'] ?? 0) ?: null;
        $current = (string) $request['status'];
        $action = (string) $this->input('action', 'approve');
        $workflow = new WorkflowDefinition();

        try {
            if ($action === 'reject') {
                $model->update($requestId, ['status' => 'Rejected', 'rejection_reason' => trim((string)$this->input('reason', ''))]);
                WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $requestId, $current, 'Rejected', 'salary_change_reject');
            } elseif ($current === 'Pending Finance Review') {
                $requiredRole = $workflow->requiredRoleFor('salary_change', 1, 'Finance Officer');
                if (!$this->userMatchesWorkflowRole($requiredRole)) {
                    throw new RuntimeException("This workflow step requires {$requiredRole} approval.");
                }
                $model->update($requestId, ['status' => 'Pending Admin Approval', 'finance_reviewed_by' => $userId]);
                WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $requestId, $current, 'Pending Admin Approval', 'salary_change_finance_review', $workflow->actionLabelFor('salary_change', 1, 'Review Salary Change'));
            } elseif ($current === 'Pending Admin Approval') {
                $requiredRole = $workflow->requiredRoleFor('salary_change', 2, 'Super Admin');
                if (!$this->userMatchesWorkflowRole($requiredRole)) {
                    throw new RuntimeException("This workflow step requires {$requiredRole} approval.");
                }
                (new EmployeeSalary())->assignAndActivate((int)$request['employee_id'], (int)$request['salary_structure_id'], (string)$request['effective_date'], [
                    'actual_basic_pay' => $request['actual_basic_pay'] !== null ? (float) $request['actual_basic_pay'] : null,
                    'actual_housing_allowance' => $request['actual_housing_allowance'] !== null ? (float) $request['actual_housing_allowance'] : null,
                    'actual_transport_allowance' => $request['actual_transport_allowance'] !== null ? (float) $request['actual_transport_allowance'] : null,
                    'actual_other_allowances' => $request['actual_other_allowances'] !== null ? (float) $request['actual_other_allowances'] : null,
                    'override_reason' => $request['override_reason'] ?? null,
                ]);
                $model->update($requestId, ['status' => 'Applied', 'admin_approved_by' => $userId]);
                WorkflowEvent::record('salary_change', 'SalaryChangeRequest', $requestId, $current, 'Applied', 'salary_change_admin_apply', $workflow->actionLabelFor('salary_change', 2, 'Apply Salary Change'));
            } else {
                throw new RuntimeException('This salary change is not awaiting approval.');
            }
            AuditLog::record('salary_change_workflow', 'Salary change request #' . $requestId . ' advanced.', 'SalaryChangeRequest', $requestId);
            Session::flash('success', 'Salary change workflow updated.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('salary-change/index');
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

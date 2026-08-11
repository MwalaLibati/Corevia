<?php

declare(strict_types=1);

class ApprovalInbox extends Model
{
    private WorkflowDefinition $workflow;

    public function __construct()
    {
        parent::__construct();
        $this->workflow = new WorkflowDefinition();
    }

    public function pendingForCurrentUser(int $limit = 50): array
    {
        $companyId = Tenant::id();
        if ($companyId <= 0) {
            return [];
        }

        $items = array_merge(
            $this->leaveItems($companyId),
            $this->advanceItems($companyId),
            $this->payrollItems($companyId),
            $this->contractItems($companyId),
            $this->onboardingItems($companyId),
            $this->salaryChangeItems($companyId)
        );

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
        });

        return array_slice($items, 0, max(1, $limit));
    }

    public function summaryForItems(array $items): array
    {
        $byType = [];
        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? 'Other');
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        ksort($byType);

        return [
            'total' => count($items),
            'by_type' => $byType,
        ];
    }

    public function summaryForCurrentUser(): array
    {
        return $this->summaryForItems($this->pendingForCurrentUser(500));
    }

    private function leaveItems(int $companyId): array
    {
        $role = $this->requiredRole('leave', 1, 'HR Officer');
        if (!$this->userMatchesRole($role)) {
            return [];
        }

        $rows = $this->fetchAllSafely(
            "SELECT lr.id, lr.created_at, lr.start_date, lr.end_date, lr.total_days,
                    e.full_name, e.employee_number, lt.name AS leave_type_name
             FROM leave_requests lr
             JOIN employees e ON e.id = lr.employee_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.status = 'Pending' AND e.company_id = :cid
             ORDER BY lr.created_at ASC",
            ['cid' => $companyId]
        );

        return array_map(function (array $row) use ($role): array {
            return $this->item(
                'Leave',
                'bi-calendar-heart',
                'leave',
                (int) $row['id'],
                (string) $row['leave_type_name'] . ' - ' . (string) $row['full_name'],
                $this->employeeSubject($row),
                'Awaiting ' . $role,
                $role,
                $this->workflow->actionLabelFor('leave', 1, 'Review Leave'),
                'approval-inbox/show/leave/' . (int) $row['id'],
                (string) ($row['created_at'] ?? ''),
                (string) $row['start_date'] . ' to ' . (string) $row['end_date'] . ' - ' . number_format((float) $row['total_days'], 1) . ' day(s)'
            );
        }, $rows);
    }

    private function advanceItems(int $companyId): array
    {
        $role = $this->requiredRole('salary_advance', 1, 'Finance Officer');
        if (!$this->userMatchesRole($role)) {
            return [];
        }

        $rows = $this->fetchAllSafely(
            "SELECT sa.id, sa.amount, sa.monthly_deduction, sa.created_at,
                    e.full_name, e.employee_number
             FROM salary_advances sa
             JOIN employees e ON e.id = sa.employee_id
             WHERE sa.status = 'Pending' AND e.company_id = :cid
             ORDER BY sa.created_at ASC",
            ['cid' => $companyId]
        );

        return array_map(function (array $row) use ($role): array {
            return $this->item(
                'Salary Advance',
                'bi-cash-coin',
                'salary_advance',
                (int) $row['id'],
                'Salary advance - ' . (string) $row['full_name'],
                $this->employeeSubject($row),
                'Awaiting ' . $role,
                $role,
                $this->workflow->actionLabelFor('salary_advance', 1, 'Review Advance'),
                'approval-inbox/show/salary_advance/' . (int) $row['id'],
                (string) ($row['created_at'] ?? ''),
                format_currency((float) $row['amount']) . ' requested'
            );
        }, $rows);
    }

    private function payrollItems(int $companyId): array
    {
        $rows = $this->fetchAllSafely(
            "SELECT pr.id, pr.pay_period, pr.run_date, pr.status, pr.created_at, pr.total_net,
                    COUNT(pi.id) AS item_count
             FROM payroll_runs pr
             LEFT JOIN payroll_items pi ON pi.payroll_run_id = pr.id
             WHERE pr.company_id = :cid
               AND pr.status IN ('Draft','HR Approved','Finance Approved','Admin Approved')
             GROUP BY pr.id, pr.pay_period, pr.run_date, pr.status, pr.created_at, pr.total_net
             ORDER BY pr.run_date ASC, pr.id ASC",
            ['cid' => $companyId]
        );

        $map = [
            'Draft' => [1, 'HR Officer', 'HR payroll review'],
            'HR Approved' => [2, 'Finance Officer', 'Finance payroll review'],
            'Finance Approved' => [3, 'Super Admin', 'Admin/director approval'],
            'Admin Approved' => [4, 'Finance Officer', 'Post and lock payroll'],
        ];

        $items = [];
        foreach ($rows as $row) {
            if ((int) ($row['item_count'] ?? 0) <= 0) {
                continue;
            }

            [$step, $fallbackRole, $stageLabel] = $map[(string) $row['status']];
            $role = $this->requiredRole('payroll', $step, $fallbackRole);
            if (!$this->userMatchesRole($role)) {
                continue;
            }

            $items[] = $this->item(
                'Payroll',
                'bi-receipt',
                'payroll',
                (int) $row['id'],
                'Payroll run - ' . (string) $row['pay_period'],
                number_format((int) $row['item_count']) . ' employee(s)',
                $stageLabel . ' - ' . $role,
                $role,
                $this->workflow->actionLabelFor('payroll', $step, 'Review Payroll'),
                'approval-inbox/show/payroll/' . (int) $row['id'],
                (string) ($row['created_at'] ?: $row['run_date']),
                'Net payroll ' . format_currency((float) ($row['total_net'] ?? 0))
            );
        }

        return $items;
    }

    private function contractItems(int $companyId): array
    {
        $rows = $this->fetchAllSafely(
            "SELECT ec.id, ec.contract_number, ec.contract_type, ec.start_date, ec.end_date,
                    ec.created_at, ec.approval_status, e.full_name, e.employee_number
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             WHERE e.company_id = :cid
               AND ec.approval_status IN ('Pending HR Review','Pending Admin Approval')
             ORDER BY ec.created_at ASC, ec.id ASC",
            ['cid' => $companyId]
        );

        $items = [];
        foreach ($rows as $row) {
            $step = (string) $row['approval_status'] === 'Pending Admin Approval' ? 2 : 1;
            $fallbackRole = $step === 2 ? 'Super Admin' : 'HR Officer';
            $role = $this->requiredRole('contract', $step, $fallbackRole);
            if (!$this->userMatchesRole($role)) {
                continue;
            }

            $items[] = $this->item(
                'Contract',
                'bi-file-earmark-text',
                'contract',
                (int) $row['id'],
                'Contract ' . (string) $row['contract_number'] . ' - ' . (string) $row['full_name'],
                $this->employeeSubject($row),
                (string) $row['approval_status'] . ' - ' . $role,
                $role,
                $this->workflow->actionLabelFor('contract', $step, 'Review Contract'),
                'approval-inbox/show/contract/' . (int) $row['id'],
                (string) ($row['created_at'] ?? ''),
                (string) $row['contract_type'] . ' - ' . (string) $row['start_date'] . ' to ' . (string) $row['end_date']
            );
        }

        return $items;
    }

    private function onboardingItems(int $companyId): array
    {
        $role = $this->requiredRole('employee_onboarding', 1, 'HR Officer');
        if (!$this->userMatchesRole($role)) {
            return [];
        }

        $rows = $this->fetchAllSafely(
            "SELECT id, invited_full_name, invited_email, expected_start_date, submitted_at, created_at
             FROM employee_onboarding_requests
             WHERE company_id = :cid AND status = 'Submitted'
             ORDER BY COALESCE(submitted_at, created_at) ASC",
            ['cid' => $companyId]
        );

        return array_map(function (array $row) use ($role): array {
            return $this->item(
                'Onboarding',
                'bi-person-plus',
                'employee_onboarding',
                (int) $row['id'],
                'Onboarding submission - ' . (string) $row['invited_full_name'],
                (string) ($row['invited_email'] ?? ''),
                'Awaiting ' . $role,
                $role,
                $this->workflow->actionLabelFor('employee_onboarding', 1, 'Review Onboarding'),
                'approval-inbox/show/employee_onboarding/' . (int) $row['id'],
                (string) (($row['submitted_at'] ?? '') ?: ($row['created_at'] ?? '')),
                !empty($row['expected_start_date']) ? 'Expected start ' . (string) $row['expected_start_date'] : 'Submitted by employee'
            );
        }, $rows);
    }

    private function salaryChangeItems(int $companyId): array
    {
        $rows = $this->fetchAllSafely(
            "SELECT scr.id, scr.status, scr.effective_date, scr.created_at,
                    e.full_name, e.employee_number, ss.name AS salary_structure_name
             FROM salary_change_requests scr
             JOIN employees e ON e.id = scr.employee_id
             LEFT JOIN salary_structures ss ON ss.id = scr.salary_structure_id
             WHERE scr.company_id = :cid
               AND scr.status IN ('Pending Finance Review','Pending Admin Approval')
             ORDER BY scr.created_at ASC",
            ['cid' => $companyId]
        );

        $items = [];
        foreach ($rows as $row) {
            $step = (string) $row['status'] === 'Pending Admin Approval' ? 2 : 1;
            $fallbackRole = $step === 2 ? 'Super Admin' : 'Finance Officer';
            $role = $this->requiredRole('salary_change', $step, $fallbackRole);
            if (!$this->userMatchesRole($role)) {
                continue;
            }

            $items[] = $this->item(
                'Salary Change',
                'bi-currency-exchange',
                'salary_change',
                (int) $row['id'],
                'Salary change - ' . (string) $row['full_name'],
                $this->employeeSubject($row),
                (string) $row['status'] . ' - ' . $role,
                $role,
                $this->workflow->actionLabelFor('salary_change', $step, 'Review Salary Change'),
                'approval-inbox/show/salary_change/' . (int) $row['id'],
                (string) ($row['created_at'] ?? ''),
                'Effective ' . (string) ($row['effective_date'] ?? '') . ' - ' . (string) ($row['salary_structure_name'] ?? '')
            );
        }

        return $items;
    }

    private function requiredRole(string $workflowType, int $stepOrder, string $fallbackRole): string
    {
        try {
            return $this->workflow->requiredRoleFor($workflowType, $stepOrder, $fallbackRole);
        } catch (Throwable) {
            return $fallbackRole;
        }
    }

    private function userMatchesRole(string $requiredRole): bool
    {
        $user = current_user() ?? [];
        $role = (string) ($user['role'] ?? '');
        $accessLevel = (string) ($user['access_level'] ?? '');

        return in_array($role, ['Super Admin', 'Admin'], true)
            || in_array($accessLevel, ['Super Admin', 'Admin'], true)
            || $role === $requiredRole
            || $accessLevel === $requiredRole;
    }

    private function fetchAllSafely(string $sql, array $params): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    private function employeeSubject(array $row): string
    {
        $number = trim((string) ($row['employee_number'] ?? ''));
        $name = trim((string) ($row['full_name'] ?? ''));

        return $number !== '' ? $name . ' (' . $number . ')' : $name;
    }

    private function item(
        string $type,
        string $icon,
        string $workflowType,
        int $id,
        string $title,
        string $subject,
        string $stage,
        string $requiredRole,
        string $actionLabel,
        string $route,
        string $createdAt,
        string $meta
    ): array {
        return [
            'type' => $type,
            'icon' => $icon,
            'workflow_type' => $workflowType,
            'id' => $id,
            'title' => $title,
            'subject' => $subject,
            'stage' => $stage,
            'required_role' => $requiredRole,
            'action_label' => $actionLabel,
            'url' => base_url($route),
            'created_at' => $createdAt,
            'meta' => $meta,
        ];
    }
}

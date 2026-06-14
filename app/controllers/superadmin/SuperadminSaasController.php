<?php

declare(strict_types=1);

class SuperadminSaasController extends Controller
{
    public function index(): void
    {
        require_superadmin();
        $ops = new SaasOperation();

        $this->renderSuperAdmin('superadmin/saas/index', [
            'title' => 'SaaS Operations',
            'data' => $ops->dashboard(),
            'plans' => $this->activePlans(),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function runControls(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $result = (new SaasOperation())->runAccountControls(14);
        Session::flash('success', 'Account controls completed: ' . $result['marked_overdue'] . ' invoice(s) marked overdue, ' . $result['overdue_suspended'] . ' overdue account(s) suspended, ' . $result['trial_suspended'] . ' expired trial(s) suspended.');
        redirect('superadmin/saas/index');
    }

    public function markRenewalReminder(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $sub = $this->findSubscription((int) $id);
        db()->prepare('UPDATE subscriptions SET renewal_reminder_sent_at = NOW(), renewal_reminder_note = :note WHERE id = :id')
            ->execute(['id' => (int) $id, 'note' => trim((string) $this->input('note', 'Renewal reminder recorded.')) ?: 'Renewal reminder recorded.']);

        (new SaasOperation())->record((int) $sub['company_id'], 'renewal_reminder', 'Renewal reminder recorded for subscription ending ' . (string) $sub['ends_at'], 'Subscription', (int) $id);
        Session::flash('success', 'Renewal reminder recorded.');
        redirect('superadmin/saas/index');
    }

    public function suspend(string $companyId = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $reason = trim((string) $this->input('reason', 'Suspended by platform admin.'));
        (new SaasOperation())->suspendCompany((int) $companyId, $reason !== '' ? $reason : 'Suspended by platform admin.');
        AuditLog::recordPlatform('company_suspended', 'Suspended company ' . (string) $companyId, 'Company', (int) $companyId);
        Session::flash('success', 'Company suspended.');
        redirect('superadmin/saas/index');
    }

    public function reactivate(string $companyId = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $reason = trim((string) $this->input('reason', 'Reactivated by platform admin.'));
        (new SaasOperation())->reactivateCompany((int) $companyId, $reason !== '' ? $reason : 'Reactivated by platform admin.');
        AuditLog::recordPlatform('company_reactivated', 'Reactivated company ' . (string) $companyId, 'Company', (int) $companyId);
        Session::flash('success', 'Company reactivated.');
        redirect('superadmin/saas/index');
    }

    public function requestPlanChange(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $subscription = $this->findSubscription((int) $this->input('subscription_id', 0));
        $requestedPlan = trim((string) $this->input('requested_plan', ''));
        $plan = $this->findPlan($requestedPlan);
        if (!$plan) {
            Session::flash('error', 'Please select a valid target plan.');
            redirect('superadmin/saas/index');
        }

        $currentOrder = (int) ($this->findPlan((string) $subscription['plan'])['sort_order'] ?? 0);
        $targetOrder = (int) ($plan['sort_order'] ?? 0);
        $changeType = $targetOrder > $currentOrder ? 'upgrade' : ($targetOrder < $currentOrder ? 'downgrade' : 'change');
        $monthlyRate = $this->money((string) $this->input('monthly_rate', ''));
        if ($monthlyRate <= 0) {
            $monthlyRate = (float) $plan['default_monthly_rate'];
        }
        $billingCycle = (string) $this->input('billing_cycle', (string) $subscription['billing_cycle']);
        $billingCycle = $billingCycle === 'Monthly' ? 'Monthly' : 'Annual';
        $billingModel = (string) $this->input('billing_model', (string) ($subscription['billing_model'] ?? 'per_user'));
        $billingModel = $billingModel === 'flat' ? 'flat' : 'per_user';

        db()->prepare(
            "INSERT INTO subscription_plan_changes
             (company_id, subscription_id, current_plan, requested_plan, change_type, effective_date, billing_model, monthly_rate, billing_cycle, status, reason, requested_by)
             VALUES (:company_id, :subscription_id, :current_plan, :requested_plan, :change_type, :effective_date, :billing_model, :monthly_rate, :billing_cycle, 'Pending', :reason, :requested_by)"
        )->execute([
            'company_id' => (int) $subscription['company_id'],
            'subscription_id' => (int) $subscription['id'],
            'current_plan' => (string) $subscription['plan'],
            'requested_plan' => $requestedPlan,
            'change_type' => $changeType,
            'effective_date' => $this->dateOrToday((string) $this->input('effective_date', date('Y-m-d'))),
            'billing_model' => $billingModel,
            'monthly_rate' => $monthlyRate,
            'billing_cycle' => $billingCycle,
            'reason' => trim((string) $this->input('reason', '')) ?: null,
            'requested_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
        ]);
        $id = (int) db()->lastInsertId();
        (new SaasOperation())->record((int) $subscription['company_id'], 'plan_change_requested', ucfirst($changeType) . ' requested from ' . (string) $subscription['plan'] . ' to ' . $requestedPlan, 'SubscriptionPlanChange', $id);

        Session::flash('success', 'Plan change request created.');
        redirect('superadmin/saas/index');
    }

    public function applyPlanChange(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }

        $change = $this->findPlanChange((int) $id);
        $targetPlan = $this->findPlan((string) $change['requested_plan']);
        if ((string) $change['status'] !== 'Pending') {
            Session::flash('error', 'Only pending plan changes can be applied.');
            redirect('superadmin/saas/index');
        }

        $db = db();
        $stmt = $db->prepare('SELECT COUNT(*) FROM employees WHERE company_id = :cid');
        $stmt->execute(['cid' => (int) $change['company_id']]);
        $employeeCount = (int) $stmt->fetchColumn();
        $months = (string) $change['billing_cycle'] === 'Monthly' ? 1 : 12;
        $price = ((string) $change['billing_model'] === 'flat' ? (float) $change['monthly_rate'] : (float) $change['monthly_rate'] * $employeeCount) * $months;
        $startsAt = (string) $change['effective_date'];
        $endsAt = date('Y-m-d', strtotime($startsAt . " +{$months} months"));

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE subscriptions SET status = 'Expired' WHERE company_id = :cid AND status = 'Active'")
                ->execute(['cid' => (int) $change['company_id']]);

            $db->prepare(
                "INSERT INTO subscriptions
                 (company_id, plan, billing_model, price, employee_count, monthly_rate, currency, billing_cycle, starts_at, ends_at, status, notes, created_by)
                 VALUES (:company_id, :plan, :billing_model, :price, :employee_count, :monthly_rate, :currency, :billing_cycle, :starts_at, :ends_at, 'Active', :notes, :created_by)"
            )->execute([
                'company_id' => (int) $change['company_id'],
                'plan' => (string) $change['requested_plan'],
                'billing_model' => (string) $change['billing_model'],
                'price' => $price,
                'employee_count' => $employeeCount,
                'monthly_rate' => (float) $change['monthly_rate'],
                'currency' => (string) ($targetPlan['currency'] ?? 'ZMW'),
                'billing_cycle' => (string) $change['billing_cycle'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => 'Created from plan change request #' . (int) $change['id'],
                'created_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
            ]);
            $newSubscriptionId = (int) $db->lastInsertId();

            $db->prepare('UPDATE companies SET subscription_plan = :plan, is_active = 1, account_status = "Active" WHERE id = :id')
                ->execute(['plan' => (string) $change['requested_plan'], 'id' => (int) $change['company_id']]);

            $db->prepare("UPDATE subscription_plan_changes SET status = 'Applied', reviewed_by = :by, reviewed_at = NOW(), created_subscription_id = :sid WHERE id = :id")
                ->execute(['by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null, 'sid' => $newSubscriptionId, 'id' => (int) $change['id']]);
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', 'Plan change could not be applied: ' . $e->getMessage());
            redirect('superadmin/saas/index');
        }

        (new SaasOperation())->record((int) $change['company_id'], 'plan_change_applied', 'Applied plan change to ' . (string) $change['requested_plan'], 'SubscriptionPlanChange', (int) $change['id']);
        Session::flash('success', 'Plan change applied and new subscription created.');
        redirect('superadmin/saas/index');
    }

    public function rejectPlanChange(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid request token.');
            redirect('superadmin/saas/index');
        }
        $change = $this->findPlanChange((int) $id);
        db()->prepare("UPDATE subscription_plan_changes SET status = 'Rejected', reviewed_by = :by, reviewed_at = NOW() WHERE id = :id")
            ->execute(['by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null, 'id' => (int) $change['id']]);
        (new SaasOperation())->record((int) $change['company_id'], 'plan_change_rejected', 'Rejected plan change to ' . (string) $change['requested_plan'], 'SubscriptionPlanChange', (int) $change['id']);
        Session::flash('success', 'Plan change rejected.');
        redirect('superadmin/saas/index');
    }

    private function activePlans(): array
    {
        return db()->query('SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
    }

    private function findPlan(string $name): ?array
    {
        $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE name = :name AND is_active = 1 LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findSubscription(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM subscriptions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Subscription not found.'); }
        return $row;
    }

    private function findPlanChange(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM subscription_plan_changes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Plan change not found.'); }
        return $row;
    }

    private function money(string $value): float
    {
        return round(max(0, (float) preg_replace('/[^\d.]/', '', $value)), 2);
    }

    private function dateOrToday(string $value): string
    {
        $date = date_create(trim($value));
        return $date ? $date->format('Y-m-d') : date('Y-m-d');
    }
}

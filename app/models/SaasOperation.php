<?php

declare(strict_types=1);

class SaasOperation extends Model
{
    protected string $table = 'saas_operation_events';

    public function dashboard(): array
    {
        $db = $this->db;

        $summary = [
            'active_companies' => (int) $db->query("SELECT COUNT(*) FROM companies WHERE is_active = 1")->fetchColumn(),
            'suspended_companies' => (int) $db->query("SELECT COUNT(*) FROM companies WHERE is_active = 0 OR account_status = 'Suspended'")->fetchColumn(),
            'trials_expiring' => (int) $db->query("SELECT COUNT(*) FROM companies WHERE is_active = 1 AND subscription_plan = 'Trial' AND trial_ends_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)")->fetchColumn(),
            'trials_expired' => (int) $db->query("SELECT COUNT(*) FROM companies WHERE is_active = 1 AND subscription_plan = 'Trial' AND trial_ends_at < CURDATE()")->fetchColumn(),
            'renewals_due' => (int) $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'Active' AND ends_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
            'overdue_invoices' => (int) $db->query("SELECT COUNT(*) FROM subscription_invoices WHERE balance_due > 0 AND due_date < CURDATE()")->fetchColumn(),
            'pending_plan_changes' => (int) $db->query("SELECT COUNT(*) FROM subscription_plan_changes WHERE status = 'Pending'")->fetchColumn(),
            'outstanding_balance' => (float) $db->query("SELECT COALESCE(SUM(balance_due),0) FROM subscription_invoices WHERE status <> 'Paid'")->fetchColumn(),
        ];

        return [
            'summary' => $summary,
            'renewals' => $this->renewalReminders(),
            'trials' => $this->trialControls(),
            'overdue' => $this->overdueAccounts(),
            'usage' => $this->usageSummaries(),
            'planChanges' => $this->planChanges(),
            'events' => $this->events(12),
        ];
    }

    public function renewalReminders(int $days = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name AS company_name, c.email AS company_email,
                    DATEDIFF(s.ends_at, CURDATE()) AS days_left
             FROM subscriptions s
             JOIN companies c ON c.id = s.company_id
             WHERE s.status = 'Active'
               AND s.ends_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY s.ends_at ASC"
        );
        $stmt->execute(['days' => $days]);
        return $stmt->fetchAll();
    }

    public function overdueAccounts(): array
    {
        return $this->db->query(
            "SELECT c.id AS company_id, c.name AS company_name, c.email, c.is_active, c.account_status,
                    COUNT(si.id) AS overdue_count,
                    COALESCE(SUM(si.balance_due), 0) AS overdue_balance,
                    MIN(si.due_date) AS oldest_due_date,
                    DATEDIFF(CURDATE(), MIN(si.due_date)) AS days_overdue
             FROM subscription_invoices si
             JOIN companies c ON c.id = si.company_id
             WHERE si.balance_due > 0 AND si.due_date < CURDATE()
             GROUP BY c.id, c.name, c.email, c.is_active, c.account_status
             ORDER BY days_overdue DESC, overdue_balance DESC"
        )->fetchAll();
    }

    public function trialControls(): array
    {
        return $this->db->query(
            "SELECT c.*, DATEDIFF(c.trial_ends_at, CURDATE()) AS days_left
             FROM companies c
             WHERE c.subscription_plan = 'Trial'
               AND c.trial_ends_at IS NOT NULL
               AND c.trial_ends_at <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
             ORDER BY c.trial_ends_at ASC"
        )->fetchAll();
    }

    public function usageSummaries(): array
    {
        return $this->db->query(
            "SELECT c.id, c.name, c.slug, c.is_active, c.account_status, c.subscription_plan,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id) AS employee_count,
                    (SELECT COUNT(*) FROM company_user_memberships m WHERE m.company_id = c.id AND m.is_active = 1) AS user_count,
                    (SELECT COUNT(*) FROM payroll_runs pr WHERE pr.company_id = c.id) AS payroll_runs,
                    (SELECT COUNT(*) FROM employee_contracts ec JOIN employees e2 ON e2.id = ec.employee_id WHERE e2.company_id = c.id) AS contracts,
                    (SELECT COUNT(*) FROM employee_generated_letters egl WHERE egl.company_id = c.id) AS generated_letters,
                    s.id AS subscription_id, s.plan, s.billing_model, s.monthly_rate, s.employee_count AS billed_employees,
                    s.currency, s.billing_cycle, s.ends_at
             FROM companies c
             LEFT JOIN subscriptions s ON s.company_id = c.id AND s.status = 'Active'
             ORDER BY c.name ASC"
        )->fetchAll();
    }

    public function planChanges(): array
    {
        return $this->db->query(
            "SELECT spc.*, c.name AS company_name
             FROM subscription_plan_changes spc
             JOIN companies c ON c.id = spc.company_id
             ORDER BY FIELD(spc.status, 'Pending', 'Approved', 'Applied', 'Rejected'), spc.created_at DESC"
        )->fetchAll();
    }

    public function events(int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT soe.*, c.name AS company_name, pa.full_name AS created_by_name
             FROM saas_operation_events soe
             LEFT JOIN companies c ON c.id = soe.company_id
             LEFT JOIN platform_admins pa ON pa.id = soe.created_by
             ORDER BY soe.created_at DESC, soe.id DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function record(?int $companyId, string $type, string $message, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        $this->insert([
            'company_id' => $companyId,
            'event_type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $message,
            'metadata' => $metadata !== [] ? json_encode($metadata) : null,
            'created_by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
        ]);
    }

    public function suspendCompany(int $companyId, string $reason): void
    {
        $this->db->prepare(
            "UPDATE companies
             SET is_active = 0, account_status = 'Suspended', suspended_at = NOW(), suspended_by = :by, suspension_reason = :reason
             WHERE id = :id"
        )->execute([
            'id' => $companyId,
            'reason' => $reason,
            'by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
        ]);
        $this->record($companyId, 'company_suspended', $reason, 'Company', $companyId);
    }

    public function reactivateCompany(int $companyId, string $reason): void
    {
        $this->db->prepare(
            "UPDATE companies
             SET is_active = 1, account_status = 'Active', reactivated_at = NOW(), reactivated_by = :by, suspension_reason = NULL
             WHERE id = :id"
        )->execute([
            'id' => $companyId,
            'by' => (int) ($_SESSION['superadmin_user']['id'] ?? 0) ?: null,
        ]);
        $this->record($companyId, 'company_reactivated', $reason, 'Company', $companyId);
    }

    public function runAccountControls(int $overdueGraceDays = 14): array
    {
        $markedOverdue = $this->db->exec(
            "UPDATE subscription_invoices
             SET status = 'Overdue', overdue_at = COALESCE(overdue_at, NOW())
             WHERE balance_due > 0 AND due_date < CURDATE() AND status NOT IN ('Paid','Overdue')"
        );

        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, MIN(si.due_date) AS oldest_due
             FROM companies c
             JOIN subscription_invoices si ON si.company_id = c.id
             WHERE c.is_active = 1
               AND si.balance_due > 0
               AND si.due_date < DATE_SUB(CURDATE(), INTERVAL :days DAY)
             GROUP BY c.id, c.name"
        );
        $stmt->execute(['days' => $overdueGraceDays]);
        $companies = $stmt->fetchAll();
        foreach ($companies as $company) {
            $this->suspendCompany((int) $company['id'], 'Automatically suspended for invoices overdue beyond ' . $overdueGraceDays . ' days.');
        }

        $trialStmt = $this->db->query(
            "SELECT id, name FROM companies
             WHERE is_active = 1 AND subscription_plan = 'Trial'
               AND trial_ends_at IS NOT NULL AND trial_ends_at < CURDATE()"
        );
        $trials = $trialStmt->fetchAll();
        foreach ($trials as $trial) {
            $this->suspendCompany((int) $trial['id'], 'Automatically suspended because the trial period has expired.');
        }

        $this->record(null, 'account_controls_run', 'Ran overdue and trial account controls.', null, null, [
            'marked_overdue' => (int) $markedOverdue,
            'overdue_suspended' => count($companies),
            'trial_suspended' => count($trials),
        ]);

        return [
            'marked_overdue' => (int) $markedOverdue,
            'overdue_suspended' => count($companies),
            'trial_suspended' => count($trials),
        ];
    }
}

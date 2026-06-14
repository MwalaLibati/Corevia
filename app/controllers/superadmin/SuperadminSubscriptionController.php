<?php

declare(strict_types=1);

class SuperadminSubscriptionController extends Controller
{

    public function index(): void
    {
        require_superadmin();
        $db = db();

        $subscriptions = $db->query(
            "SELECT s.*, c.name AS company_name, c.slug,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = s.company_id) AS current_emp_count
             FROM subscriptions s
             JOIN companies c ON c.id = s.company_id
             ORDER BY s.status ASC, s.ends_at ASC"
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/subscriptions/index', [
            'title' => 'Subscriptions & Billing',
            'subscriptions' => $subscriptions,
            'stats' => $this->getFinancialStats(),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function plans(): void
    {
        require_superadmin();

        $plans = db()->query(
            "SELECT sp.*, COUNT(spm.module_key) AS module_count
             FROM subscription_plans sp
             LEFT JOIN subscription_plan_modules spm ON spm.plan_id = sp.id
             GROUP BY sp.id
             ORDER BY sp.sort_order ASC, sp.name ASC"
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/subscriptions/plans', [
            'title' => 'Subscription Plans',
            'plans' => $plans,
            'flash' => Session::flash('success'),
            'flashErr' => Session::flash('error'),
        ]);
    }

    public function editPlan(string $id = ''): void
    {
        require_superadmin();

        $plan = $this->findPlanOrFail((int) $id);
        $stmt = db()->prepare('SELECT module_key FROM subscription_plan_modules WHERE plan_id = :id');
        $stmt->execute(['id' => (int) $plan['id']]);

        $this->renderSuperAdmin('superadmin/subscriptions/edit-plan', [
            'title' => 'Edit Subscription Plan',
            'plan' => $plan,
            'modules' => module_catalog(),
            'selectedModules' => array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
            'csrf' => Session::csrfToken(),
            'flash' => Session::flash('error'),
            'success' => Session::flash('success'),
        ]);
    }

    public function updatePlan(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/subscription/plans'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/subscription/plans');
        }

        $id = (int) $this->input('id', 0);
        $plan = $this->findPlanOrFail($id);
        $description = trim((string) $this->input('description', ''));
        $rate = $this->money((string) $this->input('default_monthly_rate', '0'));
        $cycle = (string) $this->input('default_billing_cycle', 'Annual');
        $isActive = (int) $this->input('is_active', 0) === 1 ? 1 : 0;
        $modules = array_values(array_intersect((array) ($_POST['modules'] ?? []), array_keys(module_catalog())));

        if (!in_array($cycle, ['Monthly', 'Annual'], true)) {
            $cycle = 'Annual';
        }

        $db = db();
        $db->beginTransaction();
        try {
            $db->prepare(
                'UPDATE subscription_plans
                 SET description = :description, default_monthly_rate = :rate, default_billing_cycle = :cycle, is_active = :active
                 WHERE id = :id'
            )->execute([
                'description' => $description ?: null,
                'rate' => $rate,
                'cycle' => $cycle,
                'active' => $isActive,
                'id' => $id,
            ]);

            $db->prepare('DELETE FROM subscription_plan_modules WHERE plan_id = :id')->execute(['id' => $id]);
            $insert = $db->prepare('INSERT INTO subscription_plan_modules (plan_id, module_key) VALUES (:id, :module)');
            foreach ($modules as $module) {
                $insert->execute(['id' => $id, 'module' => $module]);
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', 'Plan could not be updated: ' . $e->getMessage());
            redirect('superadmin/subscription/editPlan/' . $id);
        }

        AuditLog::recordPlatform('updated', 'Updated subscription plan ' . (string) $plan['name'], 'SubscriptionPlan', $id);
        Session::flash('success', (string) $plan['name'] . ' plan updated.');
        redirect('superadmin/subscription/editPlan/' . $id);
    }

    public function create(string $companyId = ''): void
    {
        require_superadmin();
        $db = db();

        $companies = $db->query(
            "SELECT c.id, c.name, c.slug,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id) AS emp_count
             FROM companies c WHERE c.is_active = 1 ORDER BY c.name ASC"
        )->fetchAll();

        $selected = null;
        if ($companyId !== '') {
            foreach ($companies as $co) {
                if ((int) $co['id'] === (int) $companyId) {
                    $selected = $co;
                    break;
                }
            }
        }

        $this->renderSuperAdmin('superadmin/subscriptions/create', [
            'title' => 'New Subscription',
            'csrf' => Session::csrfToken(),
            'companies' => $companies,
            'selected' => $selected,
            'plans' => $this->activePlans(),
            'rate' => $this->defaultMonthlyRate(),
            'flash' => Session::flash('error'),
        ]);
    }

    public function store(): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/subscription/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/subscription/create');
        }

        $companyId = (int) $this->input('company_id', 0);
        $plan = (string) $this->input('plan', 'Standard');
        $billingModel = (string) $this->input('billing_model', 'per_user');
        $cycle = (string) $this->input('billing_cycle', 'Annual');
        $startsAt = (string) $this->input('starts_at', date('Y-m-d'));
        $rateOverride = trim((string) $this->input('monthly_rate', ''));
        $notes = trim((string) $this->input('notes', ''));
        $adminId = (int) ($_SESSION['superadmin_user']['id'] ?? 0);

        if ($companyId < 1) {
            Session::flash('error', 'Please select a company.');
            redirect('superadmin/subscription/create');
        }

        $planRow = $this->findActivePlanByName($plan);
        if (!$planRow) {
            Session::flash('error', 'Please select a valid active plan.');
            redirect('superadmin/subscription/create/' . $companyId);
        }

        $db = db();
        $stmt = $db->prepare('SELECT COUNT(*) FROM employees WHERE company_id = :cid');
        $stmt->execute(['cid' => $companyId]);
        $empCount = (int) $stmt->fetchColumn();

        $billingModel = $billingModel === 'flat' ? 'flat' : 'per_user';
        $rate = $rateOverride !== '' ? $this->money($rateOverride) : (float) $planRow['default_monthly_rate'];
        $cycle = $cycle === 'Monthly' ? 'Monthly' : 'Annual';
        $months = $cycle === 'Monthly' ? 1 : 12;
        $price = ($billingModel === 'flat' ? $rate : $rate * $empCount) * $months;
        $endsAt = date('Y-m-d', strtotime($startsAt . " +$months months"));

        $db->prepare("UPDATE subscriptions SET status = 'Expired' WHERE company_id = :cid AND status = 'Active'")
            ->execute(['cid' => $companyId]);

        $db->prepare(
            "INSERT INTO subscriptions
             (company_id, plan, billing_model, price, employee_count, monthly_rate, currency, billing_cycle, starts_at, ends_at, status, notes, created_by)
             VALUES (:cid, :plan, :billing_model, :price, :emp, :rate, :currency, :cycle, :start, :end, 'Active', :notes, :by)"
        )->execute([
            'cid' => $companyId,
            'plan' => $plan,
            'billing_model' => $billingModel,
            'price' => $price,
            'emp' => $empCount,
            'rate' => $rate,
            'currency' => (string) $planRow['currency'],
            'cycle' => $cycle,
            'start' => $startsAt,
            'end' => $endsAt,
            'notes' => $notes ?: null,
            'by' => $adminId,
        ]);
        $subscriptionId = (int) $db->lastInsertId();

        $db->prepare('UPDATE companies SET subscription_plan = :plan WHERE id = :id')
            ->execute(['plan' => $plan, 'id' => $companyId]);

        AuditLog::recordPlatform('created', 'Created subscription for company ' . (string) $companyId . ' on ' . $plan, 'Subscription', $subscriptionId);
        Session::flash('success', 'Subscription created. Bill: ' . (string) $planRow['currency'] . ' ' . number_format($price, 2));
        redirect('superadmin/subscription/index');
    }

    public function renew(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/subscription/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/subscription/index');
        }

        $sub = $this->findOrFail((int) $id);
        $db = db();

        $stmt = $db->prepare('SELECT COUNT(*) FROM employees WHERE company_id = :cid');
        $stmt->execute(['cid' => $sub['company_id']]);
        $empCount = (int) $stmt->fetchColumn();

        $rate = (float) $sub['monthly_rate'];
        $months = $sub['billing_cycle'] === 'Monthly' ? 1 : 12;
        $billingModel = (string) ($sub['billing_model'] ?? 'per_user');
        $price = ($billingModel === 'flat' ? $rate : $rate * $empCount) * $months;

        $startBase = max(strtotime((string) $sub['ends_at']), strtotime('today'));
        $startsAt = date('Y-m-d', $startBase);
        $endsAt = date('Y-m-d', strtotime($startsAt . " +$months months"));

        $db->prepare("UPDATE subscriptions SET status = 'Expired' WHERE id = :id")
            ->execute(['id' => (int) $id]);

        $adminId = (int) ($_SESSION['superadmin_user']['id'] ?? 0);
        $db->prepare(
            "INSERT INTO subscriptions
             (company_id, plan, billing_model, price, employee_count, monthly_rate, currency, billing_cycle, starts_at, ends_at, status, created_by)
             VALUES (:cid, :plan, :billing_model, :price, :emp, :rate, :currency, :cycle, :start, :end, 'Active', :by)"
        )->execute([
            'cid' => $sub['company_id'],
            'plan' => $sub['plan'],
            'billing_model' => $billingModel,
            'price' => $price,
            'emp' => $empCount,
            'rate' => $rate,
            'currency' => (string) ($sub['currency'] ?? 'ZMW'),
            'cycle' => $sub['billing_cycle'],
            'start' => $startsAt,
            'end' => $endsAt,
            'by' => $adminId,
        ]);

        AuditLog::recordPlatform('renewed', 'Renewed subscription ' . (string) $id, 'Subscription', (int) $id);
        Session::flash('success', 'Subscription renewed. New bill: ' . (string) ($sub['currency'] ?? 'ZMW') . ' ' . number_format($price, 2) . '.');
        redirect('superadmin/subscription/index');
    }

    public function cancel(string $id = ''): void
    {
        require_superadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('superadmin/subscription/index'); }
        if (!Session::verifyCsrf((string) $this->input('_csrf', ''))) {
            Session::flash('error', 'Invalid token.');
            redirect('superadmin/subscription/index');
        }

        $this->findOrFail((int) $id);
        db()->prepare("UPDATE subscriptions SET status = 'Cancelled' WHERE id = :id")
            ->execute(['id' => (int) $id]);

        AuditLog::recordPlatform('cancelled', 'Cancelled subscription ' . (string) $id, 'Subscription', (int) $id);
        Session::flash('success', 'Subscription cancelled.');
        redirect('superadmin/subscription/index');
    }

    public function financial(): void
    {
        require_superadmin();
        $stats = $this->getFinancialStats();

        $perCompany = db()->query(
            "SELECT c.id, c.name, c.slug, c.is_active,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id) AS emp_count,
                    s.price AS annual_bill,
                    s.employee_count AS billed_emp,
                    s.monthly_rate,
                    s.billing_model,
                    s.plan, s.status AS sub_status,
                    s.starts_at, s.ends_at,
                    DATEDIFF(s.ends_at, CURDATE()) AS days_remaining
             FROM companies c
             LEFT JOIN subscriptions s ON s.company_id = c.id AND s.status = 'Active'
             ORDER BY c.is_active DESC, c.name ASC"
        )->fetchAll();

        $revenueHistory = db()->query(
            "SELECT DATE_FORMAT(s.starts_at, '%b %Y') AS period,
                    SUM(s.price) AS total,
                    COUNT(DISTINCT s.company_id) AS companies
             FROM subscriptions s
             WHERE s.status IN ('Active','Expired')
             GROUP BY YEAR(s.starts_at), MONTH(s.starts_at)
             ORDER BY MIN(s.starts_at) DESC
             LIMIT 12"
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/subscriptions/financial', [
            'title' => 'Financial Statistics',
            'stats' => $stats,
            'perCompany' => $perCompany,
            'revenueHistory' => $revenueHistory,
            'rate' => $this->defaultMonthlyRate(),
        ]);
    }

    public function getFinancialStats(): array
    {
        $db = db();

        $activeRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(price), 0) FROM subscriptions WHERE status = 'Active'"
        )->fetchColumn();

        $totalEmp = (int) $db->query(
            "SELECT COUNT(*) FROM employees e JOIN companies c ON c.id = e.company_id WHERE c.is_active = 1"
        )->fetchColumn();

        $projectedAnnual = (float) $db->query(
            "SELECT COALESCE(SUM(CASE WHEN billing_model = 'flat' THEN monthly_rate ELSE employee_count * monthly_rate END * 12), 0)
             FROM subscriptions WHERE status = 'Active'"
        )->fetchColumn();
        $projectedMonthly = (float) $db->query(
            "SELECT COALESCE(SUM(CASE WHEN billing_model = 'flat' THEN monthly_rate ELSE employee_count * monthly_rate END), 0)
             FROM subscriptions WHERE status = 'Active'"
        )->fetchColumn();

        $activeCount = (int) $db->query("SELECT COUNT(*) FROM subscriptions WHERE status='Active'")->fetchColumn();
        $expiredCount = (int) $db->query("SELECT COUNT(*) FROM subscriptions WHERE status='Expired'")->fetchColumn();
        $expiringCount = (int) $db->query(
            "SELECT COUNT(*) FROM subscriptions WHERE status='Active' AND ends_at <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();

        return [
            'active_revenue' => $activeRevenue,
            'projected_annual' => $projectedAnnual,
            'projected_monthly' => $projectedMonthly,
            'total_employees' => $totalEmp,
            'active_subs' => $activeCount,
            'expired_subs' => $expiredCount,
            'expiring_soon' => $expiringCount,
            'total_companies' => (int) $db->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
            'active_companies' => (int) $db->query('SELECT COUNT(*) FROM companies WHERE is_active=1')->fetchColumn(),
            'rate_per_emp' => $this->defaultMonthlyRate(),
        ];
    }

    private function findOrFail(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM subscriptions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Subscription not found.'); }
        return $row;
    }

    private function activePlans(): array
    {
        return db()->query(
            'SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        )->fetchAll();
    }

    private function defaultMonthlyRate(): float
    {
        try {
            $stmt = db()->prepare("SELECT default_monthly_rate FROM subscription_plans WHERE name = 'Standard' LIMIT 1");
            $stmt->execute();
            $rate = $stmt->fetchColumn();

            return $rate !== false ? (float) $rate : 0.0;
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function findPlanOrFail(int $id): array
    {
        $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); exit('Subscription plan not found.'); }
        return $row;
    }

    private function findActivePlanByName(string $name): ?array
    {
        $stmt = db()->prepare('SELECT * FROM subscription_plans WHERE name = :name AND is_active = 1 LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function money(string $value): float
    {
        return round(max(0, (float) preg_replace('/[^\d.]/', '', $value)), 2);
    }
}

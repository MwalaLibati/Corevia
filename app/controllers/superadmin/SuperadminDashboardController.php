<?php

declare(strict_types=1);

class SuperadminDashboardController extends Controller
{
    public function index(): void
    {
        require_superadmin();
        $db = db();

        $totalUsers     = (int) $db->query("SELECT COUNT(DISTINCT user_id) FROM company_user_memberships WHERE is_active = 1")->fetchColumn();
        $totalEmployees = (int) $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();

        $companies = $db->query(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM employees e WHERE e.company_id = c.id) AS employee_count,
                    (SELECT COUNT(*) FROM company_user_memberships m WHERE m.company_id = c.id AND m.is_active = 1) AS user_count,
                    s.plan AS sub_plan, s.price AS sub_price, s.monthly_rate, s.billing_model, s.currency,
                    s.ends_at AS sub_ends_at, s.status AS sub_status,
                    s.employee_count AS billed_emp
             FROM companies c
             LEFT JOIN subscriptions s ON s.company_id = c.id AND s.status = 'Active'
             ORDER BY c.created_at DESC"
        )->fetchAll();

        // Financial stats via subscription controller logic
        require_once BASE_PATH . '/app/controllers/superadmin/SuperadminSubscriptionController.php';
        $subCtrl = new SuperadminSubscriptionController();
        $financial = $subCtrl->getFinancialStats();

        $expiringCompanies = $db->query(
            "SELECT c.name, s.ends_at, DATEDIFF(s.ends_at, CURDATE()) AS days_left
             FROM subscriptions s JOIN companies c ON c.id = s.company_id
             WHERE s.status = 'Active' AND s.ends_at <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY s.ends_at ASC LIMIT 5"
        )->fetchAll();

        $this->renderSuperAdmin('superadmin/dashboard', [
            'title'             => 'Platform Dashboard',
            'financial'         => $financial,
            'companies'         => $companies,
            'totalUsers'        => $totalUsers,
            'totalEmployees'    => $totalEmployees,
            'expiringCompanies' => $expiringCompanies,
        ]);
    }
}

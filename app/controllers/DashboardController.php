<?php

declare(strict_types=1);

/**
 * Main dashboard controller.
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        require_auth();

        $db  = db();
        $cid = Tenant::id();
        $ce  = $cid > 0 ? "AND e.company_id = $cid" : '';
        $cpr = $cid > 0 ? "AND company_id = $cid" : '';

        $totals = [
            'employees'        => (int) $db->query("SELECT COUNT(*) FROM employees WHERE 1=1 $cpr")->fetchColumn(),
            'active_employees' => (int) $db->query("SELECT COUNT(*) FROM employees WHERE contract_status='Active' $cpr")->fetchColumn(),
            'salary_structures'=> (int) $db->query("SELECT COUNT(*) FROM salary_structures WHERE 1=1 $cpr")->fetchColumn(),
            'payroll_runs'     => (int) $db->query("SELECT COUNT(*) FROM payroll_runs WHERE 1=1 $cpr")->fetchColumn(),
            'draft_runs'       => (int) $db->query("SELECT COUNT(*) FROM payroll_runs WHERE status='Draft' $cpr")->fetchColumn(),
            'generated_items'  => (int) $db->query("SELECT COUNT(*) FROM payroll_items pi JOIN payroll_runs pr ON pr.id=pi.payroll_run_id WHERE 1=1 $cpr")->fetchColumn(),
            'deduction_types'  => (int) $db->query("SELECT COUNT(*) FROM deduction_types WHERE 1=1 $cpr")->fetchColumn(),
            'pending_leave'    => (int) $db->query("SELECT COUNT(*) FROM leave_requests lr JOIN employees e ON e.id=lr.employee_id WHERE lr.status='Pending' $ce")->fetchColumn(),
            'pending_advances' => (int) $db->query("SELECT COUNT(*) FROM salary_advances sa JOIN employees e ON e.id=sa.employee_id WHERE sa.status='Pending' $ce")->fetchColumn(),
            'active_advances'  => (int) $db->query("SELECT COUNT(*) FROM salary_advances sa JOIN employees e ON e.id=sa.employee_id WHERE sa.status='Active' $ce")->fetchColumn(),
            'announcements'    => (int) $db->query("SELECT COUNT(*) FROM announcements WHERE is_published=1 AND (expires_at IS NULL OR expires_at>=CURDATE()) $cpr")->fetchColumn(),
            'contracts_expiring_6m' => $this->countContractsExpiringWithinDays(183),
            'leave_liability'       => $this->leaveLiabilityTotal((int) date('Y')),
            'contract_liability'    => $this->contractLiabilityTotal(),
        ];

        $latestRunStmt = $db->prepare("SELECT id, pay_period, run_date, status, total_gross, total_deductions, total_net FROM payroll_runs WHERE 1=1 $cpr ORDER BY run_date DESC, id DESC LIMIT 1");
        $latestRunStmt->execute();
        $latestRun  = $latestRunStmt->fetch() ?: null;
        $recentRuns = (new PayrollRun())->listWithDetails();
        $recentRuns = array_slice($recentRuns, 0, 5);

        $payrollTrendStmt = $db->prepare(
            "SELECT DATE_FORMAT(run_date,'%b %Y') AS lbl, SUM(total_net) AS net
             FROM payroll_runs
             WHERE status NOT IN ('Draft') AND run_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) $cpr
             GROUP BY YEAR(run_date), MONTH(run_date)
             ORDER BY MIN(run_date) ASC"
        );
        $payrollTrendStmt->execute();
        $payrollTrend = $payrollTrendStmt->fetchAll();

        $deptChartStmt = $db->prepare(
            "SELECT COALESCE(d.name,'Unassigned') AS dept, COUNT(*) AS cnt
             FROM employees e LEFT JOIN departments d ON d.id=e.department_id
             WHERE e.contract_status='Active' $ce
             GROUP BY COALESCE(d.name,'Unassigned') ORDER BY cnt DESC LIMIT 8"
        );
        $deptChartStmt->execute();
        $deptChart = $deptChartStmt->fetchAll();

        $leaveChartStmt = $db->prepare(
            "SELECT lr.status, COUNT(*) AS cnt FROM leave_requests lr
             JOIN employees e ON e.id=lr.employee_id WHERE 1=1 $ce GROUP BY lr.status ORDER BY cnt DESC"
        );
        $leaveChartStmt->execute();
        $leaveChart = $leaveChartStmt->fetchAll();

        $pendingLeaveStmt = $db->prepare(
            "SELECT lr.id, lr.start_date, lr.end_date, lr.total_days,
                    e.full_name, e.employee_number, lt.name AS leave_type_name
             FROM leave_requests lr
             JOIN employees e  ON e.id  = lr.employee_id
             JOIN leave_types lt ON lt.id = lr.leave_type_id
             WHERE lr.status='Pending' $ce ORDER BY lr.created_at ASC LIMIT 6"
        );
        $pendingLeaveStmt->execute();
        $pendingLeave = $pendingLeaveStmt->fetchAll();

        $pendingAdvancesStmt = $db->prepare(
            "SELECT sa.id, sa.amount, sa.monthly_deduction, sa.created_at,
                    e.full_name, e.employee_number
             FROM salary_advances sa
             JOIN employees e ON e.id=sa.employee_id
             WHERE sa.status='Pending' $ce ORDER BY sa.created_at ASC LIMIT 6"
        );
        $pendingAdvancesStmt->execute();
        $pendingAdvances = $pendingAdvancesStmt->fetchAll();

        $contractModel = new EmployeeContract();
        $contractModel->autoExpire();
        $expiringContracts = $contractModel->expiringWithinDays(30);

        $todayKey = 'contract_notify_sent_' . date('Y-m-d');
        if (empty($_SESSION[$todayKey])) {
            try {
                (new ContractNotification())->dispatchAll();
            } catch (Throwable) {
            }
            try {
                $this->seedNotifications($contractModel, $totals);
            } catch (Throwable) {
            }
            $_SESSION[$todayKey] = true;
        }

        $notifModel   = new Notification();
        $userId       = (int) ($_SESSION['auth_user']['id'] ?? 0);
        $notifCount   = $notifModel->unreadCountForUser($userId);
        $notifRecent  = $notifModel->recentForUser($userId, 12);

        $this->render('dashboard/index', [
            'title'             => 'Payroll Dashboard',
            'totals'            => $totals,
            'latestRun'         => $latestRun,
            'recentRuns'        => $recentRuns,
            'expiringContracts' => $expiringContracts,
            'notifCount'        => $notifCount,
            'notifRecent'       => $notifRecent,
            'payrollTrend'      => $payrollTrend,
            'deptChart'         => $deptChart,
            'leaveChart'        => $leaveChart,
            'pendingLeave'      => $pendingLeave,
            'pendingAdvances'   => $pendingAdvances,
        ]);
    }

    private function seedNotifications(EmployeeContract $contractModel, array $totals): void
    {
        $notif = new Notification();

        $expiring = $contractModel->expiringWithinDays(30);
        foreach ($expiring as $c) {
            $msg = "Contract for {$c['employee_name']} expires on {$c['end_date']}.";
            if (!$notif->broadcastExists($msg)) {
                $notif->createBroadcast($msg, 'warning', base_url('contract/index'));
            }
        }

        $expired = $contractModel->recentlyExpired(7);
        foreach ($expired as $c) {
            $msg = "Contract for {$c['employee_name']} expired on {$c['end_date']}.";
            if (!$notif->broadcastExists($msg)) {
                $notif->createBroadcast($msg, 'danger', base_url('contract/index'));
            }
        }

        if (($totals['draft_runs'] ?? 0) > 0) {
            $msg = "{$totals['draft_runs']} payroll run(s) are in Draft and awaiting generation.";
            if (!$notif->broadcastExists($msg)) {
                $notif->createBroadcast($msg, 'info', base_url('payroll/index'));
            }
        }
    }

    private function countContractsExpiringWithinDays(int $days): int
    {
        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             WHERE e.company_id = :cid
               AND ec.status = 'Active'
               AND ec.end_date IS NOT NULL
               AND ec.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)"
        );
        $stmt->execute(['cid' => Tenant::id(), 'days' => $days]);

        return (int) $stmt->fetchColumn();
    }

    private function leaveLiabilityTotal(int $year): float
    {
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(GREATEST(lb.entitled_days - lb.used_days, 0) * (COALESCE(ss.basic_pay, 0) / 22)), 0)
             FROM leave_balances lb
             JOIN employees e ON e.id = lb.employee_id
             LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
             LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
             WHERE e.company_id = :cid
               AND lb.year = :year"
        );
        $stmt->execute(['cid' => Tenant::id(), 'year' => $year]);

        return (float) $stmt->fetchColumn();
    }

    private function contractLiabilityTotal(): float
    {
        $settings = new Setting();
        $rate = max(0.0, (float) $settings->numericValue('gratuity_rate_percent', 5.0));

        $stmt = db()->prepare(
            "SELECT ec.start_date, ec.end_date, COALESCE(ss.basic_pay, 0) AS monthly_basic
             FROM employee_contracts ec
             JOIN employees e ON e.id = ec.employee_id
             LEFT JOIN employee_salary es ON es.employee_id = e.id AND es.is_active = 1
             LEFT JOIN salary_structures ss ON ss.id = es.salary_structure_id
             WHERE e.company_id = :cid
               AND ec.status IN ('Active','Renewed')
               AND ec.approval_status = 'Approved'"
        );
        $stmt->execute(['cid' => Tenant::id()]);

        $total = 0.0;
        foreach ($stmt->fetchAll() as $row) {
            $startDate = (string) ($row['start_date'] ?? '');
            if ($startDate === '') {
                continue;
            }

            $endDate = (string) ($row['end_date'] ?? '');
            $endDate = $endDate !== '' && $endDate !== '0000-00-00' ? $endDate : date('Y-m-d');
            $months = max(0.0, (float) ((strtotime($endDate) - strtotime($startDate)) / 2629746));
            $years = $months / 12;
            $annualBasic = (float) ($row['monthly_basic'] ?? 0) * 12;
            $total += $annualBasic * ($rate / 100) * $years;
        }

        return $total;
    }
}

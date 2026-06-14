<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">Reports</h2>
        <p class="text-gray mb-0">Payroll, HR, leave, and statutory report outputs.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#1d4ed8">
            <div class="stat-label">Payroll Runs</div>
            <div class="stat-value"><?= e((string)($summary['payroll_runs'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
            <div class="stat-label">Payroll Items</div>
            <div class="stat-value"><?= e((string)($summary['generated_items'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#7c3aed">
            <div class="stat-label">Employees</div>
            <div class="stat-value"><?= e((string)($summary['employees'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#0891b2">
            <div class="stat-label">Deduction Types</div>
            <div class="stat-value"><?= e((string)($summary['deduction_types'] ?? 0)) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#dc2626">
            <div class="stat-label">Contracts Expiring</div>
            <div class="stat-value"><?= e((string)($summary['expiring_contracts'] ?? 0)) ?></div>
        </div>
    </div>
</div>

<?php
$reports = [
    ['icon'=>'bi-graph-up-arrow',   'color'=>'primary', 'title'=>'Payroll Cost Trend',   'desc'=>'Monthly movement for gross payroll cost, deductions, and net pay.', 'url'=>'report/payrollCostTrend'],
    ['icon'=>'bi-receipt',         'color'=>'primary', 'title'=>'Payroll Summary',      'desc'=>'Monthly gross, deductions, and net pay per run.',                'url'=>'report/payrollSummary'],
    ['icon'=>'bi-buildings',       'color'=>'primary', 'title'=>'Department Cost',       'desc'=>'Department-wise payroll cost distribution.',                     'url'=>'report/departmentCost'],
    ['icon'=>'bi-people',          'color'=>'success', 'title'=>'Headcount Trend',       'desc'=>'Joiners, leavers, and estimated active headcount by month.',       'url'=>'report/headcountTrend'],
    ['icon'=>'bi-percent',         'color'=>'primary', 'title'=>'Statutory Report',      'desc'=>'PAYE, NAPSA, NHIMA-ready deduction breakdown.',                  'url'=>'report/statutory'],
    ['icon'=>'bi-calendar-range',  'color'=>'success', 'title'=>'YTD Payroll Summary',  'desc'=>'Year-to-date net pay per employee with CSV export.',             'url'=>'report/ytdSummary'],
    ['icon'=>'bi-file-earmark-text','color'=>'warning', 'title'=>'Contract Expiry',      'desc'=>'Contracts expiring within a chosen date range.',                 'url'=>'report/contractExpiry'],
    ['icon'=>'bi-calendar2-x',      'color'=>'warning', 'title'=>'Leave Liability',       'desc'=>'Remaining leave valued against active salary structure.',          'url'=>'report/leaveLiability'],
    ['icon'=>'bi-safe2',            'color'=>'danger',  'title'=>'Gratuity Liability',    'desc'=>'Accrued gratuity liability using company gratuity policy.',        'url'=>'report/gratuityLiability'],
    ['icon'=>'bi-calendar-heart',  'color'=>'info',    'title'=>'Leave Utilization',     'desc'=>'Entitled vs used leave days per employee per type, with CSV.',  'url'=>'report/leaveUtilization'],
    ['icon'=>'bi-person-dash',      'color'=>'danger',  'title'=>'Employee Turnover',     'desc'=>'Leavers by month and department for workforce monitoring.',        'url'=>'report/employeeTurnover'],
    ['icon'=>'bi-arrow-left-right', 'color'=>'info',    'title'=>'Salary Variance',       'desc'=>'Latest payroll compared with previous payroll per employee.',      'url'=>'report/salaryVariance'],
    ['icon'=>'bi-cash-coin',       'color'=>'danger',  'title'=>'Salary Advance Report', 'desc'=>'All salary advances with outstanding balances and status.',      'url'=>'report/salaryAdvanceReport'],
    ['icon'=>'bi-person-check',     'color'=>'success', 'title'=>'Employee Completion',   'desc'=>'Profile and required-document readiness per employee.',          'url'=>'report/employeeCompletion'],
];
?>

<div class="row g-4">
    <?php foreach ($reports as $r): ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="mb-3" style="width:44px;height:44px;background:var(--bs-<?= $r['color'] ?>-bg-subtle,#eff6ff);border-radius:10px;display:flex;align-items:center;justify-content:center">
                    <i class="bi <?= $r['icon'] ?> text-<?= $r['color'] ?>" style="font-size:1.3rem"></i>
                </div>
                <h6 class="fw-bold mb-1"><?= e($r['title']) ?></h6>
                <p class="text-muted mb-4 flex-grow-1" style="font-size:.83rem"><?= e($r['desc']) ?></p>
                <a href="<?= e(base_url($r['url'])) ?>" class="btn btn-outline-<?= $r['color'] ?> btn-sm align-self-start">
                    <i class="bi bi-arrow-right-circle me-1"></i>Open Report
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

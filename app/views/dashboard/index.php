<?php
$trendLabels = json_encode(array_column($payrollTrend, 'lbl'));
$trendValues = json_encode(array_map('floatval', array_column($payrollTrend, 'net')));
$deptLabels  = json_encode(array_column($deptChart, 'dept'));
$deptValues  = json_encode(array_map('intval',   array_column($deptChart, 'cnt')));
$lvLabels    = json_encode(array_column($leaveChart, 'status'));
$lvValues    = json_encode(array_map('intval', array_column($leaveChart, 'cnt')));
$statusMap   = ['Draft'=>['bg-secondary-subtle','text-secondary-emphasis'],'Posted'=>['bg-info-subtle','text-info-emphasis'],'Approved'=>['bg-success-subtle','text-success-emphasis'],'Paid'=>['bg-primary-subtle','text-primary-emphasis']];
$dashboardCompany = current_company();
$dashboardCompanyName = (string) ($dashboardCompany['name'] ?? app_product_name());
$contractExpiryUrl = base_url('report/contractExpiry') . '?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d', strtotime('+6 months'));
$latestPayrollUrl = $latestRun ? base_url('payroll/edit/' . (int) $latestRun['id']) : base_url('payroll/index');
$dashboardCards = [
    [
        'label' => 'Total Employees',
        'value' => number_format((int) $totals['employees']),
        'hint' => '<span style="color:var(--ent-success);font-weight:600">' . number_format((int) $totals['active_employees']) . ' active</span>',
        'icon' => 'bi-people-fill',
        'accent' => '#1d4ed8',
        'iconBg' => '#dbeafe',
        'url' => base_url('employee/index'),
    ],
    [
        'label' => 'Contracts Expiring',
        'value' => number_format((int) $totals['contracts_expiring_6m']),
        'hint' => 'Within 6 months',
        'icon' => 'bi-file-earmark-text',
        'accent' => (int) $totals['contracts_expiring_6m'] > 0 ? '#ea580c' : '#64748b',
        'iconBg' => (int) $totals['contracts_expiring_6m'] > 0 ? '#ffedd5' : '#f1f5f9',
        'url' => $contractExpiryUrl,
    ],
    [
        'label' => 'Pending Leave',
        'value' => number_format((int) $totals['pending_leave']),
        'hint' => 'Awaiting approval',
        'icon' => 'bi-calendar-heart',
        'accent' => (int) $totals['pending_leave'] > 0 ? '#d97706' : '#64748b',
        'iconBg' => (int) $totals['pending_leave'] > 0 ? '#fef3c7' : '#f1f5f9',
        'url' => base_url('leave/index'),
    ],
    [
        'label' => 'Advance Requests',
        'value' => number_format((int) $totals['pending_advances']),
        'hint' => number_format((int) $totals['active_advances']) . ' active',
        'icon' => 'bi-cash-coin',
        'accent' => (int) $totals['pending_advances'] > 0 ? '#dc2626' : '#64748b',
        'iconBg' => (int) $totals['pending_advances'] > 0 ? '#fee2e2' : '#f1f5f9',
        'url' => base_url('salary-advance/index'),
    ],
    [
        'label' => 'Total Leave Liability',
        'value' => format_currency((float) $totals['leave_liability']),
        'hint' => date('Y') . ' remaining leave value',
        'icon' => 'bi-calendar2-x',
        'accent' => '#7c3aed',
        'iconBg' => '#ede9fe',
        'url' => base_url('report/leaveLiability') . '?year=' . date('Y'),
        'compact' => true,
    ],
    [
        'label' => 'Total Contract Liability',
        'value' => format_currency((float) $totals['contract_liability']),
        'hint' => 'Accrued contract gratuity',
        'icon' => 'bi-safe2',
        'accent' => '#0891b2',
        'iconBg' => '#cffafe',
        'url' => base_url('report/gratuityLiability'),
        'compact' => true,
    ],
    [
        'label' => 'Latest Net Payroll',
        'value' => $latestRun ? format_currency((float) $latestRun['total_net']) : '&mdash;',
        'hint' => $latestRun ? e((string) $latestRun['pay_period']) : 'No runs yet',
        'icon' => 'bi-bar-chart-line',
        'accent' => '#0f766e',
        'iconBg' => '#ccfbf1',
        'url' => $latestPayrollUrl,
        'compact' => true,
    ],
    [
        'label' => 'Payroll Runs',
        'value' => number_format((int) $totals['payroll_runs']),
        'hint' => '<span style="color:var(--ent-warning);font-weight:600">' . number_format((int) $totals['draft_runs']) . ' draft</span>',
        'icon' => 'bi-receipt',
        'accent' => '#16a34a',
        'iconBg' => '#dcfce7',
        'url' => base_url('payroll/index'),
    ],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">Payroll Dashboard</h2>
        <p class="text-gray mb-0"><?= e($dashboardCompanyName) ?> - <?= date('F Y') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary btn-sm">View Payroll</a>
        <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-primary btn-sm">Manage Employees</a>
    </div>
</div>

<!-- Row 1: Stat cards -->
<div class="row g-3 mb-4">
    <?php foreach ($dashboardCards as $card): ?>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e($card['url']) ?>" class="text-decoration-none d-block h-100" aria-label="<?= e($card['label']) ?>">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:<?= e($card['accent']) ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label"><?= e($card['label']) ?></span>
                <span class="stat-icon" style="background:<?= e($card['iconBg']) ?>;color:<?= e($card['accent']) ?>"><i class="bi <?= e($card['icon']) ?>"></i></span>
            </div>
            <div class="stat-value" style="<?= !empty($card['compact']) ? 'font-size:1.1rem' : '' ?>"><?= $card['value'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $card['hint'] ?></div>
        </div>
        </a>
    </div>
    <?php endforeach; ?>
    <?php if (false): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#1d4ed8">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Total Employees</span>
                <span class="stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-people-fill"></i></span>
            </div>
            <div class="stat-value"><?= $totals['employees'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><span style="color:var(--ent-success);font-weight:600"><?= $totals['active_employees'] ?> active</span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#16a34a">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Payroll Runs</span>
                <span class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-receipt"></i></span>
            </div>
            <div class="stat-value"><?= $totals['payroll_runs'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><span style="color:var(--ent-warning);font-weight:600"><?= $totals['draft_runs'] ?> draft</span></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#0284c7">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Payslip Items</span>
                <span class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="bi bi-file-text"></i></span>
            </div>
            <div class="stat-value"><?= $totals['generated_items'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Across all runs</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#7c3aed">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Salary Structures</span>
                <span class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="bi bi-cash-stack"></i></span>
            </div>
            <div class="stat-value"><?= $totals['salary_structures'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $totals['deduction_types'] ?> deduction type(s)</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e(base_url('leave/index')) ?>" class="text-decoration-none">
        <div class="ent-stat-card" style="--ent-stat-accent:<?= $totals['pending_leave'] > 0 ? '#d97706' : '#64748b' ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Pending Leave</span>
                <span class="stat-icon" style="background:<?= $totals['pending_leave'] > 0 ? '#fef3c7' : '#f1f5f9' ?>;color:<?= $totals['pending_leave'] > 0 ? '#d97706' : '#64748b' ?>"><i class="bi bi-calendar-heart"></i></span>
            </div>
            <div class="stat-value"><?= $totals['pending_leave'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Awaiting approval</div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e(base_url('salary-advance/index')) ?>" class="text-decoration-none">
        <div class="ent-stat-card" style="--ent-stat-accent:<?= $totals['pending_advances'] > 0 ? '#dc2626' : '#64748b' ?>">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Advance Requests</span>
                <span class="stat-icon" style="background:<?= $totals['pending_advances'] > 0 ? '#fee2e2' : '#f1f5f9' ?>;color:<?= $totals['pending_advances'] > 0 ? '#dc2626' : '#64748b' ?>"><i class="bi bi-cash-coin"></i></span>
            </div>
            <div class="stat-value"><?= $totals['pending_advances'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $totals['active_advances'] ?> active</div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= e(base_url('announcement/index')) ?>" class="text-decoration-none">
        <div class="ent-stat-card" style="--ent-stat-accent:#0891b2">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Announcements</span>
                <span class="stat-icon" style="background:#cffafe;color:#0891b2"><i class="bi bi-megaphone"></i></span>
            </div>
            <div class="stat-value"><?= $totals['announcements'] ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Published &amp; active</div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card" style="--ent-stat-accent:#0f766e">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Latest Net Payroll</span>
                <span class="stat-icon" style="background:#ccfbf1;color:#0f766e"><i class="bi bi-bar-chart-line"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.1rem"><?= $latestRun ? format_currency((float)$latestRun['total_net']) : '—' ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $latestRun ? e((string)$latestRun['pay_period']) : 'No runs yet' ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Contract alert -->
<?php if (!empty($expiringContracts)): ?>
<div class="alert alert-warning mb-4">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-18 mt-1 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong><?= count($expiringContracts) ?> contract(s) expiring within 30 days</strong>
            <div class="mt-2">
                <table class="table table-sm table-borderless mb-0">
                    <thead><tr><th>Employee</th><th>Contract No.</th><th>Type</th><th>Expires</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($expiringContracts as $ec): ?>
                    <tr>
                        <td><?= e((string)$ec['employee_name']) ?> <small class="text-muted">(<?= e((string)$ec['employee_number']) ?>)</small></td>
                        <td><?= e((string)$ec['contract_number']) ?></td>
                        <td><?= e((string)$ec['contract_type']) ?></td>
                        <td class="fw-semibold"><?= e((string)$ec['end_date']) ?></td>
                        <td><a href="<?= e(base_url('contract/renew/'.(string)$ec['id'])) ?>" class="btn btn-sm btn-warning py-0 px-2">Renew</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Row 2: Charts -->
<div class="row g-4 mb-4">
    <!-- Payroll trend line chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="ent-section-header mb-3">
                    <h6 class="mb-0 fw-bold">Net Payroll Trend <span class="text-muted fw-normal">(last 6 months)</span></h6>
                    <a href="<?= e(base_url('report/payrollSummary')) ?>" class="btn btn-sm btn-outline-secondary">Full Report</a>
                </div>
                <canvas id="payrollTrendChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <!-- Dept donut -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Active Staff by Department</h6>
                <canvas id="deptChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Leave chart + pending widgets -->
<div class="row g-4 mb-4">
    <!-- Leave by status donut -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="ent-section-header mb-3">
                    <h6 class="mb-0 fw-bold">Leave Requests by Status</h6>
                    <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <canvas id="leaveChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Pending leave requests -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="ent-section-header mb-3">
                    <h6 class="mb-0 fw-bold">
                        Pending Leave
                        <?php if ($totals['pending_leave'] > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $totals['pending_leave'] ?></span>
                        <?php endif; ?>
                    </h6>
                    <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
                </div>
                <?php if (empty($pendingLeave)): ?>
                    <p class="text-muted mb-0 text-center py-3"><i class="bi bi-check-circle me-1 text-success"></i>No pending requests</p>
                <?php else: foreach ($pendingLeave as $lr): ?>
                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <div class="flex-shrink-0" style="width:32px;height:32px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#92400e">
                        <?= mb_strtoupper(mb_substr((string)$lr['full_name'],0,2)) ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem"><?= e((string)$lr['full_name']) ?></div>
                        <div class="text-muted" style="font-size:.74rem"><?= e((string)$lr['leave_type_name']) ?> &bull; <?= e((string)$lr['start_date']) ?> (<?= number_format((float)$lr['total_days'],1) ?>d)</div>
                    </div>
                    <a href="<?= e(base_url('leave/view/'.(int)$lr['id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 7px">Review</a>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending advances -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="ent-section-header mb-3">
                    <h6 class="mb-0 fw-bold">
                        Advance Requests
                        <?php if ($totals['pending_advances'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $totals['pending_advances'] ?></span>
                        <?php endif; ?>
                    </h6>
                    <a href="<?= e(base_url('salary-advance/index')) ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
                </div>
                <?php if (empty($pendingAdvances)): ?>
                    <p class="text-muted mb-0 text-center py-3"><i class="bi bi-check-circle me-1 text-success"></i>No pending advances</p>
                <?php else: foreach ($pendingAdvances as $adv): ?>
                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <div class="flex-shrink-0" style="width:32px;height:32px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#991b1b">
                        <?= mb_strtoupper(mb_substr((string)$adv['full_name'],0,2)) ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size:.82rem"><?= e((string)$adv['full_name']) ?></div>
                        <div class="text-muted" style="font-size:.74rem">ZMW <?= number_format((float)$adv['amount'],2) ?> &bull; <?= number_format((float)$adv['monthly_deduction'],2) ?>/mo</div>
                    </div>
                    <a href="<?= e(base_url('salary-advance/index')) ?>" class="btn btn-xs btn-outline-danger" style="font-size:.72rem;padding:2px 7px">Review</a>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 4: Recent payroll runs -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="ent-section-header mb-3">
            <h6 class="mb-0 fw-bold">Recent Payroll Runs</h6>
            <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Period</th><th>Run Date</th><th>Status</th><th>Employees</th><th class="text-end">Gross</th><th class="text-end">Net Total</th></tr>
                </thead>
                <tbody>
                <?php if (empty($recentRuns)): ?>
                    <tr><td colspan="6" class="text-center text-gray py-4">No payroll runs found.</td></tr>
                <?php else: foreach ($recentRuns as $run):
                    $st = (string)($run['status'] ?? '');
                    $sc = $statusMap[$st] ?? ['bg-secondary-subtle','text-secondary-emphasis'];
                ?>
                <tr>
                    <td class="fw-semibold"><?= e((string)($run['pay_period'] ?? '')) ?></td>
                    <td><?= e((string)($run['run_date'] ?? '')) ?></td>
                    <td><span class="badge <?= $sc[0].' '.$sc[1] ?>"><?= e($st) ?></span></td>
                    <td><?= e((string)($run['item_count'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float)($run['total_gross'] ?? 0)) ?></td>
                    <td class="text-end fw-semibold"><?= format_currency((float)($run['total_net'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var chartDefaults = {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
    };

    /* Payroll trend */
    var trendCtx = document.getElementById('payrollTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= $trendLabels ?>,
                datasets: [{
                    label: 'Net Payroll (ZMW)',
                    data: <?= $trendValues ?>,
                    borderColor: '#1d4ed8',
                    backgroundColor: 'rgba(29,78,216,.08)',
                    borderWidth: 2.5,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: Object.assign({}, chartDefaults, {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: function(v){ return 'ZMW ' + Number(v).toLocaleString(); }, font: { size: 11 } }, grid: { color: 'rgba(0,0,0,.05)' } },
                    x: { ticks: { font: { size: 11 } }, grid: { display: false } }
                }
            })
        });
    }

    /* Dept donut */
    var deptCtx = document.getElementById('deptChart');
    if (deptCtx) {
        var deptPalette = ['#1d4ed8','#16a34a','#d97706','#7c3aed','#0891b2','#dc2626','#0f766e','#64748b'];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?= $deptLabels ?>,
                datasets: [{ data: <?= $deptValues ?>, backgroundColor: deptPalette, borderWidth: 2 }]
            },
            options: Object.assign({}, chartDefaults, { cutout: '62%' })
        });
    }

    /* Leave by status */
    var lvCtx = document.getElementById('leaveChart');
    if (lvCtx) {
        var lvPalette = { 'Pending':'#d97706','Approved':'#16a34a','Rejected':'#dc2626','Cancelled':'#64748b' };
        var lvLabels  = <?= $lvLabels ?>;
        var lvColors  = lvLabels.map(function(l){ return lvPalette[l] || '#94a3b8'; });
        new Chart(lvCtx, {
            type: 'doughnut',
            data: {
                labels: lvLabels,
                datasets: [{ data: <?= $lvValues ?>, backgroundColor: lvColors, borderWidth: 2 }]
            },
            options: Object.assign({}, chartDefaults, { cutout: '60%' })
        });
    }
});
</script>

<?php
$employee       = $employee ?? $emp;
$empName        = (string) ($emp['full_name'] ?? '');
$empDesig       = (string) ($emp['designation'] ?? '');
$empNo          = (string) ($emp['employee_number'] ?? '');
$firstName      = explode(' ', $empName)[0] ?: 'there';
$contractStatus = $contract ? (string) ($contract['status'] ?? 'Active') : 'No contract';
$latestNet      = $latestPayslip ? number_format((float) ($latestPayslip['net_pay'] ?? 0), 2) : '-';
$latestPeriod   = $latestPayslip ? (string) ($latestPayslip['pay_period'] ?? $latestPayslip['run_date'] ?? '-') : 'No payslip released';
$napsaTotals    = $napsaTotals ?? ['employee' => 0.0, 'employer' => 0.0, 'total' => 0.0];
$gratuity       = $gratuityEstimate ?? ['accrued_amount' => 0.0, 'eligible' => false, 'years' => 0.0, 'rate' => 0.0];
$expiry         = $contractExpiry ?? ['has_end_date' => false, 'label' => 'Open-ended', 'status' => 'No fixed expiry'];
$completion     = $profileCompletion ?? ['percent' => 0, 'missing' => []];
$hour           = (int) date('G');
$greeting       = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$statCard = static function (string $label, string $value, string $subtext, string $icon, string $accent, string $soft): void {
    ?>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:<?= e($accent) ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label"><?= e($label) ?></span>
                <span class="stat-icon" style="background:<?= e($soft) ?>;color:<?= e($accent) ?>"><i class="bi <?= e($icon) ?>"></i></span>
            </div>
            <div class="stat-value" style="font-size:1.15rem"><?= $value ?></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted);margin-top:4px"><?= e($subtext) ?></div>
        </div>
    </div>
    <?php
};
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="text-dark mb-0"><?= e($greeting) ?>, <?= e($firstName) ?></h2>
        <p class="text-muted mb-0 mt-1"><?= e($empDesig) ?> &bull; <?= e($empNo) ?> &bull; <?= e(date('D, d M Y')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('portal/notifications')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-bell me-1"></i>Notifications</a>
        <a href="<?= e(base_url('portal/payslips')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-receipt me-1"></i>My Payslips</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $statCard(
        'Latest Net Pay',
        'ZMW ' . e($latestNet),
        $latestPeriod,
        'bi-cash-stack',
        '#16a34a',
        '#dcfce7'
    );

    $statCard(
        'Contract',
        e($contractStatus),
        $contract && !empty($contract['end_date']) ? 'Until ' . (string) $contract['end_date'] : 'No end date',
        'bi-file-earmark-text',
        $contractStatus === 'Active' ? '#2563eb' : '#64748b',
        $contractStatus === 'Active' ? '#dbeafe' : '#f1f5f9'
    );

    $statCard(
        'Total Leave Left',
        e(number_format((float) $totalLeaveRemaining, 1)) . ' <span style="font-size:.8rem;font-weight:400">days</span>',
        'Across active leave types for ' . date('Y'),
        'bi-calendar-heart',
        '#0891b2',
        '#cffafe'
    );

    $statCard(
        'Total NAPSA Contributed',
        'ZMW ' . e(number_format((float) ($napsaTotals['total'] ?? 0), 2)),
        'Employee: ZMW ' . number_format((float) ($napsaTotals['employee'] ?? 0), 2) . ' | Employer: ZMW ' . number_format((float) ($napsaTotals['employer'] ?? 0), 2),
        'bi-shield-check',
        '#7c3aed',
        '#ede9fe'
    );

    if (!empty($showGratuityCard)) {
        $statCard(
            'Accumulated Gratuity',
            'ZMW ' . e(number_format((float) ($gratuity['accrued_amount'] ?? 0), 2)),
            ((float) ($gratuity['rate'] ?? 0)) . '% over ' . number_format((float) ($gratuity['years'] ?? 0), 2) . ' year(s)' . (!empty($gratuity['eligible']) ? ' | Qualified' : ' | Accruing'),
            'bi-safe2',
            '#ca8a04',
            '#fef9c3'
        );
    }

    $expiryAccent = !empty($expiry['has_end_date']) && (int) ($expiry['days'] ?? 0) < 0 ? '#dc2626' : '#0f766e';
    $expirySoft = !empty($expiry['has_end_date']) && (int) ($expiry['days'] ?? 0) < 0 ? '#fee2e2' : '#ccfbf1';
    $statCard(
        'Contract Expiry',
        e((string) ($expiry['label'] ?? 'Open-ended')) . (!empty($expiry['has_end_date']) ? ' <span style="font-size:.8rem;font-weight:400">days</span>' : ''),
        (string) ($expiry['status'] ?? 'No fixed expiry') . (!empty($expiry['end_date']) ? ' | ' . (string) $expiry['end_date'] : ''),
        'bi-hourglass-split',
        $expiryAccent,
        $expirySoft
    );
    ?>
</div>

<?php if ($activeAdvance): ?>
<div class="alert alert-info d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi bi-cash-coin fs-5 flex-shrink-0"></i>
    <div>Active advance: <strong>ZMW <?= number_format((float)$activeAdvance['amount'], 2) ?></strong> &bull;
    Outstanding: <strong>ZMW <?= number_format((float)$activeAdvance['outstanding_balance'], 2) ?></strong> &bull;
    Monthly: <strong>ZMW <?= number_format((float)$activeAdvance['monthly_deduction'], 2) ?></strong></div>
    <a href="<?= e(base_url('portal/salaryAdvance')) ?>" class="btn btn-sm btn-outline-info ms-auto">View</a>
</div>
<?php elseif ($pendingAdvance): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4 py-2">
    <i class="bi bi-hourglass-split fs-5 flex-shrink-0"></i>
    <div>Advance request of <strong>ZMW <?= number_format((float)$pendingAdvance['amount'], 2) ?></strong> is pending Finance approval.</div>
    <a href="<?= e(base_url('portal/salaryAdvance')) ?>" class="btn btn-sm btn-outline-warning ms-auto">View</a>
</div>
<?php endif; ?>

<?php if ((int) ($completion['percent'] ?? 100) < 100): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-4">
    <i class="bi bi-person-check fs-5 flex-shrink-0"></i>
    <div class="flex-grow-1">
        <div class="fw-semibold">Profile completion: <?= (int) ($completion['percent'] ?? 0) ?>%</div>
        <div style="font-size:.82rem">Missing: <?= e(implode(', ', array_slice((array) ($completion['missing'] ?? []), 0, 4))) ?><?= count((array) ($completion['missing'] ?? [])) > 4 ? '...' : '' ?></div>
    </div>
    <a href="<?= e(base_url('portal/profile')) ?>" class="btn btn-sm btn-outline-warning">Complete Profile</a>
</div>
<?php endif; ?>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-receipt me-2 text-success"></i>Latest Payslip</h6>
                    <a href="<?= e(base_url('portal/payslips')) ?>" class="btn btn-sm btn-outline-success">All</a>
                </div>
                <?php if ($latestPayslip): ?>
                    <table class="table table-sm mb-3 no-pagination">
                        <tr><td class="text-muted ps-0">Period</td><td class="fw-semibold"><?= e((string)($latestPayslip['pay_period'] ?? $latestPayslip['run_date'])) ?></td></tr>
                        <tr><td class="text-muted ps-0">Gross</td><td>ZMW <?= number_format((float)($latestPayslip['gross_pay'] ?? 0), 2) ?></td></tr>
                        <tr><td class="text-muted ps-0">Deductions</td><td class="text-danger">-ZMW <?= number_format((float)($latestPayslip['total_deductions'] ?? 0), 2) ?></td></tr>
                        <tr class="table-success"><td class="fw-bold ps-0">Net Pay</td><td class="fw-bold">ZMW <?= number_format((float)($latestPayslip['net_pay'] ?? 0), 2) ?></td></tr>
                    </table>
                    <a href="<?= e(base_url('portal/payslipView/'.(string)$latestPayslip['id'])) ?>" class="btn btn-sm btn-success w-100 mt-auto">
                        <i class="bi bi-eye me-1"></i>View Payslip
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0 text-center py-4"><i class="bi bi-info-circle me-1"></i>No approved payslips yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-safe2 me-2 text-warning"></i>Gratuity</h6>
                    <span class="badge <?= empty($showGratuityCard) ? 'bg-secondary' : (!empty($gratuity['eligible']) ? 'bg-success' : 'bg-warning text-dark') ?>">
                        <?= empty($showGratuityCard) ? 'Not Applicable' : (!empty($gratuity['eligible']) ? 'Qualified' : 'Accumulating') ?>
                    </span>
                </div>

                <div class="mb-3">
                    <div class="text-muted small">Accumulated to date</div>
                    <div class="fw-bold" style="font-size:1.55rem;color:#111827">
                        <?= empty($showGratuityCard) ? 'Not applicable' : 'ZMW ' . number_format((float) ($gratuity['accrued_amount'] ?? 0), 2) ?>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 rounded border bg-light h-100">
                            <div class="text-muted small">Years served</div>
                            <div class="fw-semibold"><?= number_format((float) ($gratuity['years'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded border bg-light h-100">
                            <div class="text-muted small">Rate</div>
                            <div class="fw-semibold"><?= number_format((float) ($gratuity['rate'] ?? 0), 2) ?>%</div>
                        </div>
                    </div>
                </div>

                <div class="text-muted small mt-auto">
                    <?php if (empty($showGratuityCard)): ?>
                        Gratuity is normally shown for fixed-term contract employees.
                    <?php else: ?>
                        Payable amount: <strong>ZMW <?= number_format((float) ($gratuity['amount'] ?? 0), 2) ?></strong>.
                        <?= !empty($gratuity['payment_timing']) ? 'Timing: ' . e((string) $gratuity['payment_timing']) . '.' : '' ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3 fw-bold"><i class="bi bi-dash-circle me-2 text-danger"></i>My Deductions</h6>
                <?php if (empty($deductions)): ?>
                    <p class="text-muted mb-0 text-center py-4"><i class="bi bi-info-circle me-1"></i>No active deductions.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 no-pagination">
                            <thead><tr><th class="ps-0">Deduction</th><th class="text-end pe-0">Amount</th></tr></thead>
                            <tbody>
                            <?php foreach ($deductions as $d): ?>
                            <?php $calculationType = (string) ($d['calculation_type'] ?? 'Fixed'); ?>
                            <tr>
                                <td class="ps-0"><?= e((string)$d['deduction_name']) ?></td>
                                <td class="text-end pe-0">
                                    <?= $calculationType === 'Percent' ? number_format((float)$d['amount'], 1).'%' : 'ZMW '.number_format((float)$d['amount'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row g-4">
            <div class="<?= !empty($upcomingLeave) ? 'col-lg-7' : 'col-12' ?>">
        <div class="card border-0 shadow-sm flex-fill">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-megaphone me-2 text-primary"></i>Noticeboard</h6>
                    <a href="<?= e(base_url('portal/announcements')) ?>" class="btn btn-sm btn-outline-primary">All</a>
                </div>
                <?php if (empty($announcements)): ?>
                    <p class="text-muted mb-0 text-center py-3" style="font-size:.82rem">No current notices.</p>
                <?php else: foreach ($announcements as $a): ?>
                <div class="py-2 border-bottom">
                    <div class="fw-semibold" style="font-size:.83rem"><?= e((string)$a['title']) ?></div>
                    <div class="text-muted" style="font-size:.74rem;line-height:1.4;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical"><?= e((string)$a['body']) ?></div>
                    <div class="text-muted mt-1" style="font-size:.68rem"><?= e(date('d M Y', strtotime((string)$a['created_at']))) ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
            </div>

        <?php if (!empty($upcomingLeave)): ?>
            <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="mb-3 fw-bold"><i class="bi bi-calendar-event me-2 text-teal"></i>Upcoming Leave</h6>
                <?php foreach ($upcomingLeave as $ul): ?>
                <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                    <i class="bi bi-circle-fill" style="font-size:.5rem;color:#16a34a"></i>
                    <div>
                        <div style="font-size:.82rem;font-weight:600"><?= e((string)$ul['leave_type_name']) ?></div>
                        <div class="text-muted" style="font-size:.74rem"><?= e((string)$ul['start_date']) ?> to <?= e((string)$ul['end_date']) ?> (<?= number_format((float)$ul['total_days'], 1) ?> days)</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

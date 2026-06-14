<?php $f = $stats; ?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold"><i class="bi bi-bar-chart-line me-2" style="color:#7c3aed"></i>Financial Report</h2>
        <p class="text-muted mb-0 mt-1 small">Revenue statistics and per-company billing breakdown.</p>
    </div>
    <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="btn btn-sm btn-outline-secondary flex-shrink-0">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="p-3 p-md-4 mb-4 rounded-3 text-white" style="background:linear-gradient(135deg,#1e1b4b 0%,#4c1d95 50%,#7c3aed 100%)">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
        <div style="font-size:2rem;opacity:.8;flex-shrink:0"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="flex-grow-1">
            <div style="font-size:.72rem;opacity:.7;font-weight:700;letter-spacing:.6px;text-transform:uppercase">Billing Terms</div>
            <div style="font-size:1.1rem;font-weight:700">Subscriptions can be billed per user or as negotiated flat monthly fees.</div>
            <div style="opacity:.75;font-size:.78rem" class="mt-1">Plan defaults are configured under Plans & Modules; company subscriptions can override the monthly rate.</div>
        </div>
        <div class="text-md-center flex-shrink-0">
            <div style="font-size:.7rem;opacity:.7;font-weight:600">CURRENT PLATFORM ARR</div>
            <div style="font-size:1.6rem;font-weight:800">ZMW <?= number_format((float)$f['projected_annual'], 0) ?></div>
            <div style="font-size:.72rem;opacity:.7"><?= (int)$f['active_subs'] ?> active subscriptions</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Contracted ARR', 'ZMW '.number_format((float)$f['active_revenue'],0), '#7c3aed', '#ede9fe', 'bi-currency-exchange', 'Billed in active subscriptions'],
        ['Projected ARR', 'ZMW '.number_format((float)$f['projected_annual'],0), '#059669', '#dcfce7', 'bi-graph-up-arrow', 'Based on active subscription terms'],
        ['Monthly Revenue', 'ZMW '.number_format((float)$f['projected_monthly'],0), '#0ea5e9', '#e0f2fe', 'bi-calendar-month', 'Current contracted monthly total'],
        ['Active Companies', $f['active_companies'].' / '.$f['total_companies'], '#d97706', '#fef3c7', 'bi-buildings', $f['active_subs'].' active subscriptions'],
    ];
    foreach ($kpis as [$lbl, $val, $col, $bg, $icon, $sub]): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.7rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px"><?= e((string)$lbl) ?></span>
                    <span style="width:32px;height:32px;background:<?= e($bg) ?>;color:<?= e($col) ?>;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
                <div style="font-size:1.35rem;font-weight:700;color:#0f172a"><?= e((string)$val) ?></div>
                <div style="font-size:.7rem;color:#94a3b8" class="mt-1"><?= e((string)$sub) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">Per-Company Billing Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive table-responsive-sa">
                    <table class="table align-middle mb-0" style="font-size:.81rem">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th class="text-center">Employees</th>
                                <th>Model</th>
                                <th class="text-end">Monthly</th>
                                <th class="text-end">Bill</th>
                                <th>Subscription</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $grandBill = 0.0;
                        $grandMonthly = 0.0;
                        $grandEmp = 0;
                        foreach ($perCompany as $c):
                            $emp = (int) $c['emp_count'];
                            $model = (string) ($c['billing_model'] ?? 'per_user');
                            $rate = (float) ($c['monthly_rate'] ?? 0);
                            $monthlyBill = $model === 'flat' ? $rate : $emp * $rate;
                            $bill = (float) ($c['annual_bill'] ?? 0);
                            $grandBill += $bill;
                            $grandMonthly += $monthlyBill;
                            $grandEmp += $emp;
                            $subStatus = $c['sub_status'] ?? null;
                            $daysLeft = $c['ends_at'] ? (int) ceil((strtotime((string)$c['ends_at']) - time()) / 86400) : null;
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string)$c['name']) ?></div>
                                <code style="font-size:.68rem"><?= e((string)$c['slug']) ?></code>
                            </td>
                            <td class="text-center fw-bold"><?= $emp ?></td>
                            <td><?= $model === 'flat' ? 'Flat' : 'Per user' ?></td>
                            <td class="text-end">ZMW <?= number_format($monthlyBill, 2) ?></td>
                            <td class="text-end fw-bold text-success">ZMW <?= number_format($bill, 2) ?></td>
                            <td>
                                <?php if ($c['ends_at']): ?>
                                <div style="font-size:.75rem"><?= e((string)$c['starts_at']) ?> to <?= e((string)$c['ends_at']) ?></div>
                                <?php if ($daysLeft !== null && $daysLeft < 60): ?>
                                <span class="badge bg-<?= $daysLeft < 30 ? 'danger' : 'warning' ?>" style="font-size:.65rem"><?= $daysLeft ?>d left</span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:.75rem">No active sub</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $badge = match($c['is_active'] ? ($subStatus ?? 'none') : 'inactive') {
                                    'Active' => ['success','Active'],
                                    'Expired' => ['danger','Expired'],
                                    'Pending' => ['warning','Pending'],
                                    'inactive' => ['secondary','Inactive'],
                                    default => ['light','No Sub'],
                                };
                                ?>
                                <span class="badge bg-<?= $badge[0] ?>"><?= $badge[1] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <th>Platform Total</th>
                                <th class="text-center"><?= $grandEmp ?></th>
                                <th></th>
                                <th class="text-end">ZMW <?= number_format($grandMonthly, 2) ?></th>
                                <th class="text-end text-success">ZMW <?= number_format($grandBill, 2) ?></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Billing by Company</h6>
                <canvas id="companyBillingChart" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($revenueHistory)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-body p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-line me-1"></i>Revenue History</h6>
        <canvas id="revenueHistoryChart" height="90"></canvas>
    </div>
</div>
<?php endif; ?>

<script>
(function(){
    var palette = ['#7c3aed','#059669','#0ea5e9','#d97706','#dc2626','#0891b2','#1d4ed8','#64748b'];
    var rhCtx = document.getElementById('revenueHistoryChart');
    if (rhCtx) {
        <?php
        $rhLabels = array_reverse(array_column($revenueHistory, 'period'));
        $rhValues = array_reverse(array_map('floatval', array_column($revenueHistory, 'total')));
        ?>
        new Chart(rhCtx, {
            type: 'bar',
            data: { labels: <?= json_encode($rhLabels) ?>, datasets: [{ data: <?= json_encode($rhValues) ?>, backgroundColor: 'rgba(124,58,237,.75)', borderRadius: 6 }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    var cbCtx = document.getElementById('companyBillingChart');
    if (cbCtx) {
        <?php
        $cbLabels = array_column($perCompany, 'name');
        $cbValues = array_map(static fn($c) => round((float)($c['annual_bill'] ?? 0), 2), $perCompany);
        ?>
        new Chart(cbCtx, {
            type: 'doughnut',
            data: { labels: <?= json_encode($cbLabels) ?>, datasets: [{ data: <?= json_encode($cbValues) ?>, backgroundColor: palette, borderWidth: 2 }] },
            options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
        });
    }
})();
</script>

<?php $f = $financial; ?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Platform Dashboard</h2>
        <p class="text-muted mb-0 mt-1 small">Financial overview and tenant management.</p>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="<?= e(base_url('superadmin/subscription/financial')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-graph-up me-1"></i><span class="d-none d-md-inline">Full </span>Financial Report
        </a>
        <a href="<?= e(base_url('superadmin/saas/index')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-command me-1"></i>SaaS Operations
        </a>
        <a href="<?= e(base_url('superadmin/company/create')) ?>" class="btn btn-sm text-white" style="background:#7c3aed">
            <i class="bi bi-plus-circle me-1"></i>Add Company
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Active ARR', 'ZMW '.number_format((float)$f['active_revenue'], 0), 'From active subscriptions', '#7c3aed', '#ede9fe', 'bi-currency-exchange'],
        ['Projected Annual', 'ZMW '.number_format((float)$f['projected_annual'], 0), 'Based on active billing terms', '#059669', '#dcfce7', 'bi-graph-up-arrow'],
        ['Monthly Revenue', 'ZMW '.number_format((float)$f['projected_monthly'], 0), 'Current contracted monthly total', '#0ea5e9', '#e0f2fe', 'bi-calendar-month'],
        ['Expiring <30 days', (string)$f['expiring_soon'], $f['active_subs'].' active / '.$f['expired_subs'].' expired', '#d97706', '#fef3c7', 'bi-exclamation-triangle'],
    ];
    foreach ($cards as [$label, $value, $sub, $color, $bg, $icon]): ?>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= e($color) ?>!important">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px"><?= e($label) ?></span>
                    <span style="width:32px;height:32px;background:<?= e($bg) ?>;color:<?= e($color) ?>;border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
                <div style="font-size:1.55rem;font-weight:700;color:#0f172a"><?= e($value) ?></div>
                <div style="font-size:.72rem;color:#64748b" class="mt-1"><?= e((string)$sub) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mb-4 p-3" style="background:linear-gradient(135deg,#1e1b4b,#312e81);border-radius:12px;color:#fff">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
        <div style="font-size:1.6rem;flex-shrink:0"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="flex-grow-1">
            <div style="font-size:.78rem;opacity:.7;font-weight:600;letter-spacing:.4px">BILLING TERMS</div>
            <div style="font-size:.95rem;font-weight:700">Plans support configurable modules, per-user billing, and negotiated flat monthly rates.</div>
            <div style="font-size:.73rem;opacity:.75" class="mt-1"><?= (int)$f['active_companies'] ?> active companies / <?= (int)$f['total_employees'] ?> employees / ZMW <?= number_format((float)$f['projected_annual'], 2) ?> projected ARR</div>
        </div>
        <div class="flex-shrink-0">
            <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="btn btn-sm" style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);white-space:nowrap">
                <i class="bi bi-credit-card me-1"></i>Manage Subscriptions
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between py-3">
                <h6 class="mb-0 fw-bold">Companies &amp; Billing</h6>
                <a href="<?= e(base_url('superadmin/company/index')) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.8rem">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th class="text-center">Employees</th>
                                <th class="text-end">Monthly</th>
                                <th class="text-end">Bill</th>
                                <th>Sub Until</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $totalMonthly = 0.0;
                        $totalBill = 0.0;
                        $totalEmpAll = 0;
                        foreach ($companies as $c):
                            $emp = (int) $c['employee_count'];
                            $currency = (string) ($c['currency'] ?? 'ZMW');
                            $monthlyRate = (float) ($c['monthly_rate'] ?? 0);
                            $monthlyBill = (string)($c['billing_model'] ?? 'per_user') === 'flat' ? $monthlyRate : $emp * $monthlyRate;
                            $bill = (float) ($c['sub_price'] ?? 0);
                            $totalMonthly += $monthlyBill;
                            $totalBill += $bill;
                            $totalEmpAll += $emp;
                            $daysLeft = $c['sub_ends_at'] ? (int) ceil((strtotime((string)$c['sub_ends_at']) - time()) / 86400) : null;
                            $expClass = ($daysLeft !== null && $daysLeft < 30) ? 'text-danger fw-bold' : (($daysLeft !== null && $daysLeft < 60) ? 'text-warning' : '');
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string)$c['name']) ?></div>
                                <div class="text-muted" style="font-size:.7rem"><code><?= e((string)$c['slug']) ?></code></div>
                            </td>
                            <td class="text-center fw-semibold"><?= $emp ?></td>
                            <td class="text-end"><?= e($currency) ?> <?= number_format($monthlyBill, 0) ?></td>
                            <td class="text-end fw-semibold text-success"><?= e($currency) ?> <?= number_format($bill, 0) ?></td>
                            <td>
                                <?php if ($c['sub_ends_at']): ?>
                                <span class="<?= $expClass ?>"><?= e(date('d M Y', strtotime((string)$c['sub_ends_at']))) ?></span>
                                <?php if ($daysLeft !== null && $daysLeft < 60): ?><div style="font-size:.68rem"><?= $daysLeft ?>d left</div><?php endif; ?>
                                <?php else: ?><span class="text-muted">No subscription</span><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $c['is_active'] ? 'success' : 'danger' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= e(base_url('superadmin/company/view/'.$c['id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:2px 7px"><i class="bi bi-eye"></i></a>
                                    <a href="<?= e(base_url('superadmin/subscription/create/'.$c['id'])) ?>" class="btn btn-xs btn-outline-success" style="font-size:.7rem;padding:2px 7px" title="New Subscription"><i class="bi bi-plus-circle"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Platform Total</th>
                                <th class="text-center"><?= $totalEmpAll ?></th>
                                <th class="text-end fw-bold">ZMW <?= number_format($totalMonthly, 0) ?></th>
                                <th class="text-end fw-bold text-success">ZMW <?= number_format($totalBill, 0) ?></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4 d-flex flex-column gap-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3">Platform Counts</h6>
                <?php
                $pstats = [
                    ['Companies', $f['total_companies'], '#7c3aed'],
                    ['Active Tenants', $f['active_companies'], '#059669'],
                    ['Total Employees', $f['total_employees'], '#1d4ed8'],
                    ['Admin Users', $totalUsers, '#d97706'],
                    ['Active Subs', $f['active_subs'], '#059669'],
                    ['Expired Subs', $f['expired_subs'], '#dc2626'],
                ];
                foreach ($pstats as [$lbl, $val, $col]): ?>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <span style="font-size:.8rem;color:#64748b"><?= e((string)$lbl) ?></span>
                    <span style="font-weight:700;color:<?= e($col) ?>;font-size:.9rem"><?= e((string)$val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($expiringCompanies)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-clock-history me-1"></i>Expiring Soon</h6>
                <?php foreach ($expiringCompanies as $ec): ?>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <div>
                        <div style="font-size:.8rem;font-weight:600"><?= e((string)$ec['name']) ?></div>
                        <div style="font-size:.7rem;color:#dc2626"><?= e((string)$ec['ends_at']) ?></div>
                    </div>
                    <span class="badge bg-danger"><?= (int)$ec['days_left'] ?>d</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-3">Quick Actions</h6>
                <div class="d-flex flex-column gap-2">
                    <a href="<?= e(base_url('superadmin/subscription/create')) ?>" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-plus-circle me-1"></i>New Subscription
                    </a>
                    <a href="<?= e(base_url('superadmin/subscription/plans')) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-sliders me-1"></i>Plans & Modules
                    </a>
                    <a href="<?= e(base_url('superadmin/company/create')) ?>" class="btn btn-sm" style="background:#7c3aed;color:#fff">
                        <i class="bi bi-building-add me-1"></i>Onboard Company
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

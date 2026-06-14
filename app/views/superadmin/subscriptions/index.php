<?php $f = $stats; ?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Subscriptions &amp; Billing</h2>
        <p class="text-muted mb-0 mt-1 small">Manage tenant subscriptions, negotiated pricing, and renewals.</p>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="<?= e(base_url('superadmin/subscription/plans')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-sliders me-1"></i><span class="d-none d-sm-inline">Plans</span>
        </a>
        <a href="<?= e(base_url('superadmin/subscription/financial')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-graph-up me-1"></i><span class="d-none d-sm-inline">Financial Report</span>
        </a>
        <a href="<?= e(base_url('superadmin/subscription/create')) ?>" class="btn btn-sm text-white" style="background:#7c3aed">
            <i class="bi bi-plus-circle me-1"></i>New Subscription
        </a>
    </div>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-success"><?= e((string) $flash) ?></div>
<?php endif; ?>
<?php if (!empty($flashErr)): ?>
<div class="alert alert-danger"><?= e((string) $flashErr) ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php
    $scards = [
        ['Active ARR', 'ZMW '.number_format((float)$f['active_revenue'],0), 'bi-currency-exchange', '#ede9fe', '#7c3aed'],
        ['Projected Annual', 'ZMW '.number_format((float)$f['projected_annual'],0), 'bi-graph-up-arrow', '#dcfce7', '#059669'],
        ['Monthly Revenue', 'ZMW '.number_format((float)$f['projected_monthly'],0), 'bi-calendar-month', '#e0f2fe', '#0ea5e9'],
        ['Active Subs', $f['active_subs'], 'bi-check-circle-fill', '#dcfce7', '#059669'],
        ['Expired Subs', $f['expired_subs'], 'bi-x-circle-fill', '#fee2e2', '#dc2626'],
        ['Expiring <30d', $f['expiring_soon'], 'bi-exclamation-triangle', '#fef3c7', '#d97706'],
    ];
    foreach ($scards as [$lbl, $val, $icon, $bg, $ic]): ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.68rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.3px"><?= e((string)$lbl) ?></span>
                    <span style="width:26px;height:26px;background:<?= e($bg) ?>;color:<?= e($ic) ?>;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.78rem"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:#0f172a"><?= e((string)$val) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive table-responsive-sa">
            <table class="table align-middle mb-0" style="font-size:.81rem">
                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th class="text-center">Emp @ Billing</th>
                        <th class="text-center">Current Emp</th>
                        <th class="text-end">Monthly</th>
                        <th class="text-end">Bill</th>
                        <th>Period</th>
                        <th class="text-center">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No subscriptions yet.</td></tr>
                <?php else: foreach ($subscriptions as $s):
                    $billedEmp = (int) $s['employee_count'];
                    $currentEmp = (int) $s['current_emp_count'];
                    $monthlyRate = (float) $s['monthly_rate'];
                    $billingModel = (string) ($s['billing_model'] ?? 'per_user');
                    $monthlyBill = $billingModel === 'flat' ? $monthlyRate : $billedEmp * $monthlyRate;
                    $bill = (float) $s['price'];
                    $daysLeft = (int) ceil((strtotime((string)$s['ends_at']) - time()) / 86400);
                    $statusColor = ['Active'=>'success','Expired'=>'danger','Cancelled'=>'secondary','Pending'=>'warning'][$s['status']] ?? 'secondary';
                    $isActive = $s['status'] === 'Active';
                    $currency = (string) ($s['currency'] ?? 'ZMW');
                ?>
                <tr class="<?= $s['status'] === 'Expired' ? 'table-light' : '' ?>">
                    <td>
                        <div class="fw-semibold"><?= e((string)$s['company_name']) ?></div>
                        <code style="font-size:.7rem"><?= e((string)$s['slug']) ?></code>
                    </td>
                    <td>
                        <span class="badge bg-secondary"><?= e((string)$s['plan']) ?></span>
                        <div class="text-muted" style="font-size:.68rem"><?= $billingModel === 'flat' ? 'Flat' : 'Per user' ?></div>
                    </td>
                    <td class="text-center"><?= $billedEmp ?></td>
                    <td class="text-center">
                        <?= $currentEmp ?>
                        <?php if ($billingModel !== 'flat' && $currentEmp > $billedEmp): ?>
                            <span title="More employees than last billed" class="text-warning"><i class="bi bi-arrow-up-circle-fill"></i></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= e($currency) ?> <?= number_format($monthlyBill, 0) ?></td>
                    <td class="text-end fw-semibold <?= $isActive ? 'text-success' : 'text-muted' ?>">
                        <?= e($currency) ?> <?= number_format($bill, 0) ?>
                    </td>
                    <td>
                        <div style="font-size:.75rem"><?= e((string)$s['starts_at']) ?></div>
                        <div style="font-size:.75rem" class="<?= $isActive && $daysLeft < 30 ? 'text-danger fw-bold' : 'text-muted' ?>">
                            to <?= e((string)$s['ends_at']) ?>
                            <?php if ($isActive && $daysLeft <= 60): ?>
                            <span class="ms-1 badge bg-<?= $daysLeft < 30 ? 'danger' : 'warning' ?>" style="font-size:.65rem"><?= $daysLeft ?>d left</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge bg-<?= $statusColor ?>"><?= e((string)$s['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($isActive): ?>
                            <?php if (empty($s['invoice_id'])): ?>
                            <form method="post" action="<?= e(base_url('superadmin/invoice/createFromSubscription/'.$s['id'])) ?>" class="d-inline" onsubmit="return confirm('Create an invoice for this subscription?')">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:2px 8px" title="Create Invoice">
                                    <i class="bi bi-receipt"></i> Invoice
                                </button>
                            </form>
                            <?php else: ?>
                            <a href="<?= e(base_url('superadmin/invoice/view/'.$s['invoice_id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:2px 8px">
                                <i class="bi bi-receipt"></i> Invoice
                            </a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(base_url('superadmin/subscription/renew/'.$s['id'])) ?>" class="d-inline" onsubmit="return confirm('Renew this subscription?')">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-xs btn-outline-success" style="font-size:.7rem;padding:2px 8px" title="Renew">
                                    <i class="bi bi-arrow-clockwise"></i> Renew
                                </button>
                            </form>
                            <form method="post" action="<?= e(base_url('superadmin/subscription/cancel/'.$s['id'])) ?>" class="d-inline" onsubmit="return confirm('Cancel this subscription?')">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size:.7rem;padding:2px 8px" title="Cancel">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <a href="<?= e(base_url('superadmin/subscription/create/'.$s['company_id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.7rem;padding:2px 8px">
                                <i class="bi bi-plus-circle"></i> New
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3 p-3 rounded" style="background:#f1f5f9;font-size:.78rem;color:#64748b">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Billing:</strong> Plans provide default monthly rates. Individual subscriptions can override the rate and use either per-user or flat monthly pricing.
</div>

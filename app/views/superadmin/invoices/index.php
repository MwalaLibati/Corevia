<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Invoices &amp; Payments</h2>
        <p class="text-muted mb-0 mt-1 small">Track subscription invoices, collections, balances, and payment history.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/invoice/affiliates')) ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-people me-1"></i>Corevia Affiliates
        </a>
        <a href="<?= e(base_url('superadmin/subscription/index')) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-credit-card me-1"></i>Subscriptions
        </a>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string) $flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string) $flashErr) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <?php
    $total = 0.0; $paid = 0.0; $balance = 0.0; $overdue = 0;
    foreach (($invoices ?? []) as $invoice) {
        $total += (float) $invoice['total_amount'];
        $paid += (float) $invoice['paid_amount'];
        $balance += (float) $invoice['balance_due'];
        if ((float) $invoice['balance_due'] > 0 && strtotime((string) $invoice['due_date']) < strtotime('today')) { $overdue++; }
    }
    $cards = [
        ['Invoiced', 'ZMW '.number_format($total, 0), 'bi-receipt', '#ede9fe', '#7c3aed'],
        ['Collected', 'ZMW '.number_format($paid, 0), 'bi-cash-coin', '#dcfce7', '#059669'],
        ['Outstanding', 'ZMW '.number_format($balance, 0), 'bi-hourglass-split', '#fef3c7', '#d97706'],
        ['Overdue', (string) $overdue, 'bi-exclamation-triangle', '#fee2e2', '#dc2626'],
    ];
    foreach ($cards as [$label, $value, $icon, $bg, $color]): ?>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.7rem;color:#64748b;font-weight:700;text-transform:uppercase"><?= e($label) ?></span>
                    <span style="width:30px;height:30px;background:<?= e($bg) ?>;color:<?= e($color) ?>;border-radius:7px;display:flex;align-items:center;justify-content:center"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
                <div style="font-size:1.25rem;font-weight:800;color:#0f172a"><?= e($value) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.82rem">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Due</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">No invoices yet. Create one from a subscription.</td></tr>
                <?php else: foreach ($invoices as $invoice):
                    $status = (string) $invoice['status'];
                    $statusColor = ['Paid'=>'success','Partially Paid'=>'warning','Sent'=>'primary','Unpaid'=>'danger','Draft'=>'secondary','Overdue'=>'danger'][$status] ?? 'secondary';
                    $isOverdue = (float) $invoice['balance_due'] > 0 && strtotime((string) $invoice['due_date']) < strtotime('today');
                ?>
                    <tr>
                        <td class="fw-semibold"><?= e((string) $invoice['invoice_number']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= e((string) $invoice['company_name']) ?></div>
                            <code style="font-size:.68rem"><?= e((string) $invoice['slug']) ?></code>
                        </td>
                        <td><?= e((string) ($invoice['subscription_plan'] ?? '-')) ?></td>
                        <td class="<?= $isOverdue ? 'text-danger fw-semibold' : '' ?>"><?= e((string) $invoice['due_date']) ?></td>
                        <td class="text-end"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['total_amount'], 2) ?></td>
                        <td class="text-end"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['paid_amount'], 2) ?></td>
                        <td class="text-end fw-semibold"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['balance_due'], 2) ?></td>
                        <td class="text-center"><span class="badge bg-<?= $statusColor ?>"><?= e($status) ?></span></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('superadmin/invoice/view/' . (string) $invoice['id'])) ?>" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

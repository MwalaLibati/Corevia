<?php
$status = (string) $invoice['status'];
$statusColor = ['Paid'=>'success','Partially Paid'=>'warning','Sent'=>'primary','Unpaid'=>'danger','Draft'=>'secondary','Overdue'=>'danger'][$status] ?? 'secondary';
?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">Invoice <?= e((string) $invoice['invoice_number']) ?></h2>
        <p class="text-muted mb-0 mt-1 small"><?= e((string) $invoice['company_name']) ?> / <?= e((string) ($invoice['subscription_plan'] ?? 'Subscription')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/invoice/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
        <a href="<?= e(base_url('superadmin/invoice/print/' . (string) $invoice['id'])) ?>" class="btn btn-sm btn-outline-dark" target="_blank">Print/PDF</a>
        <form method="post" action="<?= e(base_url('superadmin/invoice/email/' . (string) $invoice['id'])) ?>" onsubmit="return confirm('Email this invoice to the company billing email?')">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <button type="submit" class="btn btn-sm btn-outline-success">Email Invoice</button>
        </form>
        <?php if (!in_array($status, ['Paid'], true)): ?>
        <form method="post" action="<?= e(base_url('superadmin/invoice/markSent/' . (string) $invoice['id'])) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <button type="submit" class="btn btn-sm btn-outline-primary">Mark Sent</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string) $flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string) $flashErr) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <div class="text-muted small">Bill To</div>
                        <div class="fw-bold"><?= e((string) $invoice['company_name']) ?></div>
                        <div class="text-muted small"><?= e((string) ($invoice['company_email'] ?? '')) ?></div>
                        <div class="text-muted small"><?= e((string) ($invoice['company_phone'] ?? '')) ?></div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?= $statusColor ?> mb-2"><?= e($status) ?></span>
                        <div class="small text-muted">Issue: <?= e((string) $invoice['issue_date']) ?></div>
                        <div class="small text-muted">Due: <?= e((string) $invoice['due_date']) ?></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($lines ?? []) as $line): ?>
                            <tr>
                                <td><?= e((string) $line['description']) ?></td>
                                <td class="text-end"><?= number_format((float) $line['quantity'], 2) ?></td>
                                <td class="text-end"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $line['unit_price'], 2) ?></td>
                                <td class="text-end fw-semibold"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $line['line_total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <div style="min-width:260px">
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Subtotal</span><strong><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['subtotal'], 2) ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Paid</span><strong class="text-success"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['paid_amount'], 2) ?></strong></div>
                        <div class="d-flex justify-content-between py-2"><span>Balance</span><strong class="text-danger"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $invoice['balance_due'], 2) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th><th class="text-end">Receipt</th></tr></thead>
                        <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No payments recorded.</td></tr>
                        <?php else: foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= e((string) $payment['paid_at']) ?></td>
                                <td><?= e((string) ($payment['payment_method'] ?? '-')) ?></td>
                                <td><?= e((string) ($payment['payment_reference'] ?? '-')) ?></td>
                                <td class="text-end fw-semibold"><?= e((string) $invoice['currency']) ?> <?= number_format((float) $payment['amount'], 2) ?></td>
                                <td class="text-end"><a href="<?= e(base_url('superadmin/invoice/receipt/' . (string)$payment['id'])) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">Receipt</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="mb-3">Record Payment</h5>
                <?php if ((float) $invoice['balance_due'] <= 0): ?>
                    <div class="alert alert-success">This invoice is fully paid.</div>
                <?php else: ?>
                <form method="post" action="<?= e(base_url('superadmin/invoice/recordPayment/' . (string) $invoice['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="paid_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= e((string) $invoice['currency']) ?></span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= e(number_format((float) $invoice['balance_due'], 2, '.', '')) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Method</label>
                        <input type="text" name="payment_method" class="form-control" placeholder="Bank transfer, cash, mobile money">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference</label>
                        <input type="text" name="payment_reference" class="form-control" placeholder="Receipt or bank reference">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn text-white w-100" style="background:#7c3aed" onclick="return confirm('Record this payment?')">Record Payment</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

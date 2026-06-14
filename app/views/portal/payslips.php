<div class="portal-page-header">
    <h2><i class="bi bi-receipt me-2"></i>My Payslips</h2>
    <p>All approved payslips for your account.</p>
</div>

<div class="portal-card">
    <?php if (empty($payslips)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-receipt fs-1 d-block mb-3"></i>
            No approved payslips found.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Pay Period</th>
                        <th class="text-end">Gross Pay</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net Pay</th>
                        <th class="text-center">Payment</th>
                        <th class="text-center">Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($payslips as $slip): ?>
                    <tr>
                        <td><strong><?= e((string)($slip['pay_period'] ?? $slip['run_date'])) ?></strong></td>
                        <td class="text-end">ZMW <?= number_format((float)($slip['gross_pay'] ?? 0), 2) ?></td>
                        <td class="text-end text-danger">- ZMW <?= number_format((float)($slip['total_deductions'] ?? 0), 2) ?></td>
                        <td class="text-end fw-bold text-success">ZMW <?= number_format((float)($slip['net_pay'] ?? 0), 2) ?></td>
                        <td class="text-center">
                            <?php
                            $paid = (float) ($slip['paid_amount'] ?? 0);
                            $balance = (float) ($slip['balance_due'] ?? $slip['net_pay'] ?? 0);
                            $status = $balance <= 0.005 ? 'Paid' : ($paid > 0 ? 'Partially Paid' : 'Not Paid');
                            $badge = $status === 'Paid' ? 'success' : ($status === 'Partially Paid' ? 'warning text-dark' : 'secondary');
                            ?>
                            <span class="badge bg-<?= e($badge) ?>"><?= e($status) ?></span>
                            <?php if ($paid > 0 || $balance > 0): ?>
                                <div class="text-muted" style="font-size:.72rem">
                                    Paid <?= e(format_currency($paid)) ?><?php if ($balance > 0): ?> / Bal <?= e(format_currency($balance)) ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-muted" style="font-size:.8rem"><?= e((string)($slip['run_date'] ?? '')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('portal/payslipView/' . (string)$slip['id'])) ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

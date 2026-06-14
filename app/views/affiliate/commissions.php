<div class="mb-4">
    <h4 class="mb-0">Commission Ledger</h4>
    <p class="text-muted mb-0 small">Every commission is generated from a recorded subscription payment.</p>
</div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Payment Ref</th><th>Payment</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($commissions as $c): ?>
        <tr><td><?= e((string)$c['earned_at']) ?></td><td><?= e((string)$c['company_name']) ?></td><td><?= e((string)$c['invoice_number']) ?></td><td><?= e((string)($c['payment_reference'] ?? '-')) ?></td><td>ZMW <?= number_format((float)$c['payment_amount'], 2) ?></td><td><?= e((string)$c['commission_rate']) ?>%</td><td>ZMW <?= number_format((float)$c['commission_amount'], 2) ?></td><td><?= e((string)$c['status']) ?></td></tr>
    <?php endforeach; if (empty($commissions)): ?><tr><td colspan="8" class="text-center text-muted py-4">No commission has been earned yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div></div>

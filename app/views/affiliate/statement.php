<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div><h4 class="mb-0">Affiliate Statement</h4><p class="text-muted mb-0 small">Commission movement and payout balance.</p></div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('affiliate/dashboard/statement?export=csv')) ?>" class="btn btn-sm btn-outline-primary">CSV</a>
        <a href="<?= e(base_url('affiliate/dashboard/statement?export=xls')) ?>" class="btn btn-sm btn-outline-success">Excel</a>
    </div>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([['Opening', $statement['opening_balance']], ['Earned', $statement['earned']], ['Approved', $statement['approved']], ['Paid', $statement['paid']], ['Reversed', $statement['reversed']], ['Closing', $statement['closing_balance']]] as $card): ?>
        <div class="col-sm-6 col-xl-2"><div class="ent-stat-card h-100"><span class="stat-label"><?= e($card[0]) ?></span><div class="stat-value" style="font-size:1.05rem">ZMW <?= number_format((float)$card[1], 2) ?></div></div></div>
    <?php endforeach; ?>
</div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Payment</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($statement['items'] as $item): ?>
        <tr><td><?= e((string)$item['earned_at']) ?></td><td><?= e((string)($item['company_name'] ?? '-')) ?></td><td><?= e((string)($item['invoice_number'] ?? '-')) ?></td><td>ZMW <?= number_format((float)$item['payment_amount'], 2) ?></td><td><?= e((string)$item['commission_rate']) ?>%</td><td>ZMW <?= number_format((float)$item['commission_amount'], 2) ?></td><td><?= e((string)$item['status']) ?></td></tr>
    <?php endforeach; if (empty($statement['items'])): ?><tr><td colspan="7" class="text-center text-muted py-4">No statement activity yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div></div>

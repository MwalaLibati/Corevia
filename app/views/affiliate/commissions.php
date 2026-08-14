<div class="mb-4">
    <h4 class="mb-0">Commission Ledger</h4>
    <p class="text-muted mb-0 small">Every commission is generated from a recorded subscription payment.</p>
</div>

<?php
$totals = ['Pending' => 0.0, 'Approved' => 0.0, 'Paid' => 0.0, 'Reversed' => 0.0, 'Total' => 0.0];
foreach (($commissions ?? []) as $commission) {
    $status = (string)($commission['status'] ?? 'Pending');
    $amount = (float)($commission['commission_amount'] ?? 0);
    if (isset($totals[$status])) {
        $totals[$status] += $amount;
    }
    if ($status !== 'Reversed') {
        $totals['Total'] += $amount;
    }
}
?>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Total Earned', $totals['Total'], 'bi-trophy'],
        ['Pending', $totals['Pending'], 'bi-hourglass-split'],
        ['Approved', $totals['Approved'], 'bi-check2-circle'],
        ['Paid', $totals['Paid'], 'bi-cash-stack'],
    ] as $card): ?>
        <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label"><?= e($card[0]) ?></span><div class="stat-value" style="font-size:1.15rem">ZMW <?= number_format((float)$card[1], 2) ?></div><div class="small text-muted"><i class="bi <?= e($card[2]) ?> me-1"></i>Commission value</div></div></div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Payment Ref</th><th>Payment</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($commissions as $c): ?>
        <?php
        $status = (string)($c['status'] ?? 'Pending');
        $badgeClass = match ($status) {
            'Paid' => 'bg-success',
            'Approved' => 'bg-primary',
            'Reversed' => 'bg-danger',
            default => 'bg-warning text-dark',
        };
        ?>
        <tr><td><?= e((string)$c['earned_at']) ?></td><td><?= e((string)$c['company_name']) ?></td><td><?= e((string)$c['invoice_number']) ?></td><td><?= e((string)($c['payment_reference'] ?? '-')) ?></td><td>ZMW <?= number_format((float)$c['payment_amount'], 2) ?></td><td><?= e((string)$c['commission_rate']) ?>%</td><td>ZMW <?= number_format((float)$c['commission_amount'], 2) ?></td><td><span class="badge <?= e($badgeClass) ?>"><?= e($status) ?></span></td></tr>
    <?php endforeach; if (empty($commissions)): ?><tr><td colspan="8" class="text-center text-muted py-4">No commission has been earned yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div></div>

<div class="mb-4">
    <h4 class="mb-0">My Referred Companies</h4>
    <p class="text-muted mb-0 small">Companies linked to your Corevia affiliate account.</p>
</div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Company</th><th>Status</th><th>Referred</th><th>Total Commission</th><th>Pending/Paid</th></tr></thead>
    <tbody>
    <?php foreach ($companies as $c): ?>
        <tr><td><strong><?= e((string)$c['company_name']) ?></strong><div class="text-muted small"><?= e((string)$c['company_email']) ?></div></td><td><?= e((string)$c['referral_status']) ?></td><td><?= e((string)$c['referred_at']) ?></td><td>ZMW <?= number_format((float)$c['total_commission'], 2) ?></td><td>ZMW <?= number_format((float)$c['unpaid_commission'], 2) ?> / ZMW <?= number_format((float)$c['paid_commission'], 2) ?></td></tr>
    <?php endforeach; if (empty($companies)): ?><tr><td colspan="5" class="text-center text-muted py-4">No companies linked yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div></div>

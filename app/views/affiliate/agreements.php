<div class="mb-4"><h4 class="mb-0">Affiliate Agreements</h4><p class="text-muted mb-0 small">Agreement records issued by Corevia.</p></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0">
    <thead><tr><th>Agreement</th><th>Status</th><th>Effective</th><th>Expiry</th><th>Reminder</th></tr></thead>
    <tbody>
    <?php foreach ($agreements as $agreement): ?>
        <tr><td><strong><?= e((string)$agreement['agreement_number']) ?></strong><div class="text-muted small"><?= e((string)$agreement['title']) ?></div></td><td><span class="badge bg-light text-dark border"><?= e((string)$agreement['status']) ?></span></td><td><?= e((string)($agreement['effective_date'] ?? '-')) ?></td><td><?= e((string)($agreement['expiry_date'] ?? '-')) ?></td><td><?= e((string)($agreement['renewal_reminder_at'] ?? '-')) ?></td></tr>
    <?php endforeach; if (empty($agreements)): ?><tr><td colspan="5" class="text-center text-muted py-4">No agreements issued yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</div></div></div>

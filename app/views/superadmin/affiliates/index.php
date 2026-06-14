<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">Corevia Affiliates</h4>
        <p class="text-muted mb-0 small">Referral partners earning commission from actual paid subscription receipts.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="<?= e(base_url('superadmin/invoice/affiliateSync')) ?>" onsubmit="return confirm('Sync affiliate commissions from existing paid receipts?')">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>Sync Commissions</button>
        </form>
        <a href="<?= e(base_url('superadmin/invoice/affiliateCreate')) ?>" class="btn btn-sm text-white" style="background:#7c3aed">
            <i class="bi bi-plus-circle me-1"></i>Add Affiliate
        </a>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<?php if (empty($ready)): ?>
    <div class="alert alert-warning">Affiliate tables are not installed yet. Run <strong>Docs/affiliate_portal_migration.sql</strong>.</div>
<?php endif; ?>

<?php $analytics = $analytics ?? []; ?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="ent-stat-card"><span class="stat-label">Commission Liability</span><div class="stat-value">ZMW <?= number_format((float)($analytics['commission_liability'] ?? 0), 2) ?></div></div></div>
    <div class="col-md-4"><div class="ent-stat-card"><span class="stat-label">Payouts Due This Month</span><div class="stat-value">ZMW <?= number_format((float)($analytics['payouts_due_this_month'] ?? 0), 2) ?></div></div></div>
    <div class="col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><h6 class="fw-bold mb-2">Leads by Stage</h6><?php foreach (($analytics['leads_by_stage'] ?? []) as $row): ?><div class="d-flex justify-content-between small border-bottom py-1"><span><?= e((string)$row['stage']) ?></span><strong><?= (int)$row['total'] ?></strong></div><?php endforeach; if (empty($analytics['leads_by_stage'])): ?><p class="text-muted small mb-0">No leads yet.</p><?php endif; ?></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Affiliate</th><th>Code</th><th>KYC</th><th>Companies</th><th>Lifetime</th><th>Pending</th><th>Paid</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($affiliates)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No affiliates yet.</td></tr>
                <?php else: foreach ($affiliates as $a): ?>
                    <?php $kycReady = trim((string)($a['nrc_number'] ?? '')) !== '' && trim((string)($a['tpin'] ?? '')) !== '' && (int)($a['document_count'] ?? 0) > 0; ?>
                    <tr>
                        <td><strong><?= e((string)$a['full_name']) ?></strong><div class="text-muted small"><?= e((string)$a['email']) ?></div></td>
                        <td><span class="badge bg-light text-dark border"><?= e((string)$a['affiliate_code']) ?></span></td>
                        <td><span class="badge <?= $kycReady ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $kycReady ? 'Complete' : 'Pending' ?></span></td>
                        <td><?= (int)($a['company_count'] ?? 0) ?></td>
                        <td>ZMW <?= number_format((float)($a['lifetime_commission'] ?? 0), 2) ?></td>
                        <td>ZMW <?= number_format((float)($a['pending_commission'] ?? 0), 2) ?></td>
                        <td>ZMW <?= number_format((float)($a['paid_commission'] ?? 0), 2) ?></td>
                        <td><span class="badge <?= (int)$a['is_active'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)$a['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                        <td class="text-end"><a href="<?= e(base_url('superadmin/invoice/affiliateView/' . (string)$a['id'])) ?>" class="btn btn-sm btn-outline-primary">Open</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

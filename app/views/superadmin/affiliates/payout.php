<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div><h4 class="mb-0">Payout <?= e((string)$batch['payout_reference']) ?></h4><p class="text-muted mb-0 small"><?= e((string)$batch['affiliate_name']) ?> &bull; <?= e((string)$batch['status']) ?></p></div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/invoice/affiliatePayoutPrint/' . (string)$batch['id'])) ?>" target="_blank" class="btn btn-sm btn-outline-danger">PDF/Print</a>
        <form method="post" action="<?= e(base_url('superadmin/invoice/affiliatePayoutEmail/' . (string)$batch['id'])) ?>"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><button class="btn btn-sm btn-outline-primary">Email Statement</button></form>
        <a href="<?= e(base_url('superadmin/invoice/affiliateView/' . (string)$batch['affiliate_id'])) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>
<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>
<div class="row g-3 mb-4">
    <?php foreach ([['Gross',$batch['gross_amount']], ['Tax',$batch['tax_amount']], ['Net',$batch['net_amount']]] as $stat): ?><div class="col-md-4"><div class="ent-stat-card"><span class="stat-label"><?= e($stat[0]) ?></span><div class="stat-value">ZMW <?= number_format((float)$stat[1], 2) ?></div></div></div><?php endforeach; ?>
</div>
<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <form method="post" action="<?= e(base_url('superadmin/invoice/affiliatePayoutStatus/' . (string)$batch['id'])) ?>" class="row g-3 align-items-end">
        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['Submitted','Approved','Paid','Rejected','Voided'] as $status): ?><option <?= (string)$batch['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Payment Reference</label><input name="payment_reference" class="form-control" value="<?= e((string)($batch['payment_reference'] ?? '')) ?>" placeholder="Auto-generated if paid"></div>
        <div class="col-md-4"><button class="btn btn-outline-primary">Update Payout</button></div>
    </form>
</div></div>
<div class="card border-0 shadow-sm"><div class="card-body"><div class="table-responsive">
<table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Gross</th><th>Tax</th><th>Net</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?= e((string)$item['earned_at']) ?></td><td><?= e((string)($item['company_name'] ?? '-')) ?></td><td><?= e((string)($item['invoice_number'] ?? '-')) ?></td><td>ZMW <?= number_format((float)$item['gross_amount'], 2) ?></td><td>ZMW <?= number_format((float)$item['tax_amount'], 2) ?></td><td>ZMW <?= number_format((float)$item['net_amount'], 2) ?></td></tr><?php endforeach; ?>
</tbody></table>
</div></div></div>

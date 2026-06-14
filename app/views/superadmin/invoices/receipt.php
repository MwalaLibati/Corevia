<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Payment Receipt</title>
<style>
body{font-family:Arial,sans-serif;background:#e5e7eb;color:#111827;margin:0}.bar{position:fixed;top:0;left:0;right:0;background:#065f46;color:#fff;padding:10px 18px;display:flex;gap:10px;align-items:center}.bar strong{flex:1}.bar a,.bar button{border:0;border-radius:4px;padding:7px 12px;text-decoration:none;background:#fff;color:#065f46;font-weight:700}.page{width:148mm;min-height:210mm;background:#fff;margin:64px auto 24px;padding:18mm;box-shadow:0 10px 30px rgba(15,23,42,.18)}h1{margin:0 0 4px}.muted{color:#64748b}.row{display:flex;justify-content:space-between;border-bottom:1px solid #e5e7eb;padding:10px 0}.amount{font-size:28px;font-weight:800;color:#047857}@media print{body{background:#fff}.bar{display:none}.page{margin:0;box-shadow:none;width:auto;min-height:auto}}
</style>
</head>
<body>
<div class="bar"><strong>Payment Receipt</strong><a href="<?= e(base_url('superadmin/invoice/view/' . (string)$payment['invoice_id'])) ?>">Back</a><button onclick="window.print()">Print / Save PDF</button></div>
<div class="page">
    <h1><?= e(app_vendor_name()) ?></h1>
    <div class="muted"><?= e(app_product_name()) ?> SaaS Billing</div>
    <hr style="margin:22px 0">
    <h2>Payment Receipt</h2>
    <div class="amount"><?= e((string)$payment['currency']) ?> <?= number_format((float)$payment['amount'], 2) ?></div>
    <div class="row"><span>Company</span><strong><?= e((string)$payment['company_name']) ?></strong></div>
    <div class="row"><span>Invoice</span><strong><?= e((string)$payment['invoice_number']) ?></strong></div>
    <div class="row"><span>Payment Date</span><strong><?= e((string)$payment['paid_at']) ?></strong></div>
    <div class="row"><span>Method</span><strong><?= e((string)($payment['payment_method'] ?? '-')) ?></strong></div>
    <div class="row"><span>Reference</span><strong><?= e((string)($payment['payment_reference'] ?? '-')) ?></strong></div>
    <div class="row"><span>Invoice Balance</span><strong><?= e((string)$payment['currency']) ?> <?= number_format((float)$payment['balance_due'], 2) ?></strong></div>
    <?php if (!empty($payment['notes'])): ?><p class="muted" style="margin-top:18px"><?= nl2br(e((string)$payment['notes'])) ?></p><?php endif; ?>
</div>
</body>
</html>

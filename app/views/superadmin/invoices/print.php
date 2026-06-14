<?php
$status = (string) ($invoice['status'] ?? 'Unpaid');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice <?= e((string)$invoice['invoice_number']) ?></title>
<style>
body{font-family:Arial,sans-serif;background:#e5e7eb;color:#111827;margin:0}
.bar{position:fixed;top:0;left:0;right:0;background:#312e81;color:#fff;padding:10px 18px;display:flex;gap:10px;align-items:center}
.bar strong{flex:1}.bar a,.bar button{border:0;border-radius:4px;padding:7px 12px;text-decoration:none;background:#fff;color:#312e81;font-weight:700}
.page{width:210mm;min-height:297mm;background:#fff;margin:64px auto 24px;padding:20mm;box-shadow:0 10px 30px rgba(15,23,42,.18)}
.top{display:flex;justify-content:space-between;border-bottom:3px solid #111827;padding-bottom:16px;margin-bottom:24px}
h1{margin:0;font-size:26px}.muted{color:#64748b}.badge{display:inline-block;padding:5px 10px;border-radius:999px;background:#e0e7ff;color:#312e81;font-weight:700}
table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}th{background:#f8fafc}.right{text-align:right}.totals{margin-left:auto;width:300px;margin-top:18px}.totals div{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e5e7eb}
@media print{body{background:#fff}.bar{display:none}.page{margin:0;box-shadow:none;width:auto;min-height:auto}}
</style>
</head>
<body>
<div class="bar"><strong>Invoice <?= e((string)$invoice['invoice_number']) ?></strong><a href="<?= e(base_url('superadmin/invoice/view/' . (string)$invoice['id'])) ?>">Back</a><button onclick="window.print()">Print / Save PDF</button></div>
<div class="page">
    <div class="top">
        <div>
            <h1><?= e(app_vendor_name()) ?></h1>
            <div class="muted"><?= e(app_product_name()) ?> SaaS Billing</div>
        </div>
        <div class="right">
            <h2>Invoice</h2>
            <div class="badge"><?= e($status) ?></div>
            <div class="muted">No: <?= e((string)$invoice['invoice_number']) ?></div>
        </div>
    </div>
    <div style="display:flex;justify-content:space-between;gap:24px">
        <div>
            <div class="muted">Bill To</div>
            <strong><?= e((string)$invoice['company_name']) ?></strong><br>
            <?= e((string)($invoice['company_email'] ?? '')) ?><br>
            <?= nl2br(e((string)($invoice['company_address'] ?? ''))) ?>
        </div>
        <div class="right">
            <div>Issue Date: <?= e((string)$invoice['issue_date']) ?></div>
            <div>Due Date: <?= e((string)$invoice['due_date']) ?></div>
            <div>Plan: <?= e((string)($invoice['subscription_plan'] ?? 'Subscription')) ?></div>
        </div>
    </div>
    <table>
        <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Total</th></tr></thead>
        <tbody>
        <?php foreach (($lines ?? []) as $line): ?>
        <tr><td><?= e((string)$line['description']) ?></td><td class="right"><?= number_format((float)$line['quantity'],2) ?></td><td class="right"><?= e((string)$invoice['currency']) ?> <?= number_format((float)$line['unit_price'],2) ?></td><td class="right"><?= e((string)$invoice['currency']) ?> <?= number_format((float)$line['line_total'],2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="totals">
        <div><span>Subtotal</span><strong><?= e((string)$invoice['currency']) ?> <?= number_format((float)$invoice['subtotal'],2) ?></strong></div>
        <div><span>Tax</span><strong><?= e((string)$invoice['currency']) ?> <?= number_format((float)$invoice['tax_amount'],2) ?></strong></div>
        <div><span>Total</span><strong><?= e((string)$invoice['currency']) ?> <?= number_format((float)$invoice['total_amount'],2) ?></strong></div>
        <div><span>Paid</span><strong><?= e((string)$invoice['currency']) ?> <?= number_format((float)$invoice['paid_amount'],2) ?></strong></div>
        <div><span>Balance</span><strong><?= e((string)$invoice['currency']) ?> <?= number_format((float)$invoice['balance_due'],2) ?></strong></div>
    </div>
</div>
</body>
</html>

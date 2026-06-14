<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($title) ?></title>
    <style>
        @page { margin: 16mm; }
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        .letterhead { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 20px; }
        .letterhead img { width: 86px; max-height: 72px; object-fit: contain; }
        h1 { margin: 0; font-size: 20px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { text-align: left; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 7px; }
        td { border: 1px solid #e2e8f0; padding: 7px; vertical-align: top; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 36px; }
        .sig-line { border-top: 1px solid #111827; padding-top: 8px; min-height: 40px; }
        .footer { margin-top: 28px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 11px; color: #64748b; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="position:fixed;right:16px;top:16px;padding:8px 12px">Print / Save PDF</button>
    <div class="letterhead">
        <img src="<?= e(company_logo_url($company)) ?>" alt="Logo">
        <div>
            <h1><?= e((string)($company['name'] ?? app_product_name())) ?></h1>
            <div class="muted"><?= e((string)($company['address'] ?? '')) ?></div>
            <div class="muted"><?= e((string)($company['email'] ?? '')) ?> <?= !empty($company['phone']) ? ' | ' . e((string)$company['phone']) : '' ?></div>
        </div>
    </div>

    <h2><?= e($title) ?></h2>
    <p class="muted"><?= e($description) ?></p>
    <p class="muted">Generated: <?= e(date('d M Y H:i')) ?></p>

    <table>
        <thead><tr><?php foreach ($headers as $header): ?><th><?= e((string)$header) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="<?= count($headers) ?>">No data found.</td></tr>
        <?php else: foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $cell): ?><td><?= e(is_float($cell) ? number_format($cell, 2) : (string)$cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-line"><?= e($signatory !== '' ? $signatory : 'Prepared By') ?><br><span class="muted"><?= e($signatoryTitle !== '' ? $signatoryTitle : 'Authorised Officer') ?></span></div>
        <div class="sig-line">Approved By<br><span class="muted">Signature and date</span></div>
    </div>
    <?php if (trim((string)$footer) !== ''): ?><div class="footer"><?= e((string)$footer) ?></div><?php endif; ?>
</body>
</html>

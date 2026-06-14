<?php $titleText = (string) ($letter['title'] ?? 'Employee Letter'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= e($titleText) ?></title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color:#111827; margin: 42px; line-height:1.65; }
        .actions { font-family: Arial, sans-serif; margin-bottom: 24px; }
        .btn { display:inline-block; padding:8px 12px; border:1px solid #111827; border-radius:6px; color:#111827; text-decoration:none; }
        .letterhead { border-bottom: 2px solid #111827; margin-bottom: 24px; padding-bottom: 12px; }
        h1 { margin:0; font-size: 24px; }
        h2 { font-size: 20px; text-transform: uppercase; letter-spacing: .04em; }
        @media print { .actions { display:none; } body { margin: 24mm; } }
    </style>
</head>
<body>
    <div class="actions">
        <a href="javascript:window.print()" class="btn">Print</a>
        <a href="<?= e(base_url('employee/profile/' . (string) ($letter['employee_id'] ?? 0))) ?>" class="btn">Back</a>
    </div>
    <?= $letter['body_html'] ?? '' ?>
</body>
</html>

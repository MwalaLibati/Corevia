<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Company | <?= e(app_product_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{background:linear-gradient(135deg,#0f172a 0%,#1e40af 58%,#0f766e 100%);font-family:Inter,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .panel{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 18px 45px rgba(15,23,42,.12);max-width:620px;width:100%;padding:28px}
        .title{font-size:24px;font-weight:700;color:#111827;margin:0 0 6px}
        .sub{color:#64748b;margin:0 0 22px;font-size:14px}
        .company{display:flex;align-items:center;justify-content:space-between;gap:16px;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin-bottom:12px;background:#fff}
        .company:hover{border-color:#2563eb;background:#f8fbff}
        .name{font-weight:650;color:#111827}
        .role{font-size:12px;color:#64748b;margin-top:3px}
        .btn-select{background:#1e40af;color:#fff;border:0;border-radius:8px;padding:9px 14px;font-weight:600}
        .alert{border-radius:8px;padding:12px 14px;margin-bottom:16px;background:#fef2f2;color:#b91c1c}
        .muted{color:#64748b;font-size:13px;margin-top:18px}
    </style>
</head>
<body>
<div class="panel">
    <h1 class="title">Choose Company</h1>
    <p class="sub"><?= e(app_product_name()) ?> by <?= e(app_vendor_name()) ?></p>
    <p class="sub">Signed in as <?= e((string) ($user['email'] ?? '')) ?>. Select the company you want to work in.</p>

    <?php if (!empty($flashError)): ?>
        <div class="alert"><?= e((string) $flashError) ?></div>
    <?php endif; ?>

    <?php if (empty($memberships)): ?>
        <p class="muted">No active company access is linked to this account.</p>
        <a href="<?= e(base_url('auth/logout')) ?>" class="btn btn-outline-secondary">Back to login</a>
    <?php else: ?>
        <?php foreach ($memberships as $membership): ?>
            <form method="post" action="<?= e(base_url('auth/selectCompany')) ?>" class="company">
                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                <input type="hidden" name="company_id" value="<?= e((string) $membership['company_id']) ?>">
                <div>
                    <div class="name"><?= e((string) $membership['company_name']) ?></div>
                    <div class="role"><?= e((string) $membership['role_name']) ?></div>
                </div>
                <button type="submit" class="btn-select">Enter</button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>

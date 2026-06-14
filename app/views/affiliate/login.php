<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corevia Affiliates Login</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body style="background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
<div class="card border-0 shadow-lg" style="max-width:420px;width:100%;border-radius:14px">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;border-radius:12px;background:#0f766e;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px"><i class="bi bi-diagram-3-fill fs-4"></i></div>
            <h4 class="mb-1">Corevia Affiliates</h4>
            <p class="text-muted mb-0 small">Track referrals, earnings, and payouts.</p>
        </div>
        <?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>
        <?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
        <form method="post" action="<?= e(base_url('affiliate/auth/loginStore')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
            <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
            <button class="btn w-100 text-white" style="background:#0f766e">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>

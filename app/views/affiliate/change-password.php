<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Corevia Affiliates</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body style="background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px">
<div class="card border-0 shadow-lg" style="max-width:460px;width:100%;border-radius:14px">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <div style="width:48px;height:48px;border-radius:12px;background:#0f766e;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 12px"><i class="bi bi-key-fill fs-4"></i></div>
            <h4 class="mb-1">Create Your Password</h4>
            <p class="text-muted mb-0 small">Your account was created with a temporary password. Choose a private password to continue.</p>
        </div>
        <?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>
        <form method="post" action="<?= e(base_url('affiliate/auth/changePasswordStore')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required autofocus>
                <div class="form-text">At least 10 characters with uppercase, lowercase, and a number.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button class="btn w-100 text-white" style="background:#0f766e">Save Password</button>
        </form>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Change Password | <?= e(app_product_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-primary: #1e40af; --brand-light: #3b82f6; --brand-dark: #1e3a8a; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-primary) 52%, var(--brand-light) 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 44px 40px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
        }
        .logo-container { text-align: center; margin-bottom: 28px; }
        .logo-container img { height: 54px; width: auto; margin-bottom: 16px; }
        .login-title { margin-bottom: 8px; color: #1f2937; font-size: 26px; font-weight: 700; }
        .login-subtitle { color: #6b7280; font-size: 14px; line-height: 1.5; }
        .form-group { margin-bottom: 20px; }
        .form-control {
            height: 52px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0 16px;
            font-size: 15px;
            background: #fff;
        }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 4px rgba(30, 64, 175, .1); }
        .btn-login {
            height: 52px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-light) 100%);
            color: #fff;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(30, 64, 175, .3);
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo-container">
        <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>">
        <h1 class="login-title">Change Password</h1>
        <p class="login-subtitle">This account was created with a temporary password. Choose a private password before continuing.</p>
    </div>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('auth/forcePasswordChangeStore')) ?>">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
        <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="New password (min 8 chars)" minlength="8" required autofocus>
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="password_confirm" placeholder="Confirm new password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-login w-100">Save Password</button>
    </form>
</div>
<script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>

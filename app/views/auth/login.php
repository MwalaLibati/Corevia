<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="description" content="<?= e(app_product_name() . ' - ' . app_product_tagline()) ?>">
    <title>Login | <?= e(app_product_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --brand-primary: #1e40af; --brand-light: #3b82f6; --brand-dark: #1e3a8a; }
        body {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-primary) 50%, var(--brand-light) 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 48px 40px;
            max-width: 420px;
            width: 100%;
            animation: fadeInUp 0.6s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo-container img {
            height: 56px;
            width: auto;
            margin-bottom: 16px;
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .login-subtitle {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-control {
            height: 52px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(30, 64, 175, 0.1);
            outline: none;
        }
        .form-control::placeholder {
            color: #9ca3af;
        }
        .btn-login {
            height: 52px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-light) 100%);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: none;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(30, 64, 175, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
        }
        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #dc2626;
        }
        .login-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            margin-top: 28px;
            font-size: 14px;
        }
        .login-links a {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.3s ease;
            font-weight: 500;
        }
        .login-links a:hover {
            color: var(--brand-primary);
        }
        .login-links .divider {
            color: #d1d5db;
            font-weight: 400;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 40px 28px;
                margin: 16px;
            }
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="logo-container">
        <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>">
        <h1 class="login-title"><?= e(app_product_name()) ?></h1>
        <p class="login-subtitle"><?= e(app_product_tagline()) ?><br><span style="font-size:12px;color:#94a3b8">A <?= e(app_vendor_name()) ?> product</span></p>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(base_url('auth/login')) ?>">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
        <div class="form-group">
            <input type="email" class="form-control" name="email" placeholder="Email address" required autofocus>
        </div>
        <div class="form-group">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-login w-100">Sign In</button>
    </form>

    <div class="login-links">
        <a href="<?= e(base_url('auth/forgotPassword')) ?>">Forgot password?</a>
        <span class="divider">|</span>
        <a href="<?= e(base_url('portal/login')) ?>">Employee Portal</a>
    </div>
</div>

<script src="<?= e(asset('assets/js/jquery-3.6.0.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>

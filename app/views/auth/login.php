<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(app_product_name() . ' - ' . app_product_tagline()) ?>">
    <title>Sign In | <?= e(app_product_name()) ?></title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth-login.css') . '?v=20261007-login') ?>">
</head>
<body class="auth-page" style="--auth-accent:#2563eb;--auth-accent-dark:#1d4ed8;--auth-secondary:#059669">
<main class="auth-shell">
    <section class="auth-showcase" aria-labelledby="welcome-title">
        <div class="auth-brand">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="Stonesoft IT Solutions">
            <span>StoneSoft Corevia<small>Payroll &amp; HR</small></span>
        </div>

        <div class="auth-showcase-copy">
            <div class="auth-eyebrow">Secure HR workspace</div>
            <h1 id="welcome-title">Welcome back</h1>
            <p>Manage your people, payroll, attendance and compliance from one connected workspace.</p>
        </div>

        <img class="auth-art" src="<?= e(asset('assets/img/login-workspace.png')) ?>" alt="HR, payroll and employee management workspace">
        <p class="auth-showcase-footer">A <?= e(app_vendor_name()) ?> product</p>
    </section>

    <section class="auth-form-panel" aria-labelledby="login-title">
        <div class="auth-form-wrap">
            <div class="auth-portal-label"><i class="bi bi-building-lock"></i> Admin &amp; HR portal</div>
            <h2 id="login-title">Sign in to your account</h2>
            <p class="auth-form-intro">Enter the credentials issued for your company administrator account.</p>

            <?php if (!empty($flashSuccess)): ?>
                <div class="auth-alert auth-alert-success" data-auth-alert><?= e((string)$flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
                <div class="auth-alert auth-alert-danger" data-auth-alert><?= e((string)$flashError) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('auth/login')) ?>" data-auth-form>
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="auth-field">
                    <label class="auth-label" for="admin-email">Email address</label>
                    <input id="admin-email" type="email" class="auth-control" name="email" placeholder="name@company.com" autocomplete="username" required autofocus>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="admin-password">Password</label>
                    <div class="auth-password">
                        <input id="admin-password" type="password" class="auth-control" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="#admin-password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="auth-form-meta">
                    <a class="auth-link" href="<?= e(base_url('auth/forgotPassword')) ?>">Forgot password?</a>
                </div>

                <button type="submit" class="auth-submit"><i class="bi bi-box-arrow-in-right"></i><span>Sign in securely</span></button>
            </form>

            <div class="auth-divider">Other access</div>
            <a class="auth-other-link" href="<?= e(base_url('portal/login')) ?>"><i class="bi bi-person-badge"></i>Employee self-service portal</a>
            <p class="auth-security-note"><i class="bi bi-shield-check"></i> Your session and credentials are protected.</p>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/auth-login.js') . '?v=20261007-login') ?>"></script>
</body>
</html>

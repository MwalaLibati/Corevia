<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Reset your <?= e(app_product_name()) ?> administrator password.">
    <title>Forgot Password | <?= e(app_product_name()) ?></title>
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
            <div class="auth-eyebrow">Secure account recovery</div>
            <h1 id="welcome-title">Let's get you back in</h1>
            <p>Request a secure password reset without exposing whether an email address is registered on the platform.</p>
        </div>

        <img class="auth-art" src="<?= e(asset('assets/img/login-workspace.png')) ?>" alt="Secure HR and payroll account recovery workspace">
        <p class="auth-showcase-footer">A <?= e(app_vendor_name()) ?> product</p>
    </section>

    <section class="auth-form-panel" aria-labelledby="reset-title">
        <div class="auth-form-wrap">
            <div class="auth-portal-label"><i class="bi bi-key"></i> Password recovery</div>
            <h2 id="reset-title">Reset your password</h2>
            <p class="auth-form-intro">Enter your administrator email address. If it is registered, we will email a secure reset link that remains valid for one hour.</p>

            <?php if (!empty($flashSuccess)): ?>
                <div class="auth-alert auth-alert-success" data-auth-alert><?= e((string)$flashSuccess) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
                <div class="auth-alert auth-alert-danger" data-auth-alert><?= e((string)$flashError) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('auth/forgotPasswordStore')) ?>" data-auth-form data-loading-label="Sending link...">
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="auth-field">
                    <label class="auth-label" for="recovery-email">Email address</label>
                    <input id="recovery-email" type="email" class="auth-control" name="email" placeholder="name@company.com" autocomplete="email" required autofocus>
                </div>

                <button type="submit" class="auth-submit"><i class="bi bi-envelope-arrow-up"></i><span>Send reset link</span></button>
            </form>

            <div class="auth-divider">Return to sign in</div>
            <a class="auth-other-link" href="<?= e(base_url('auth/login')) ?>"><i class="bi bi-arrow-left"></i>Back to Admin / HR login</a>
            <p class="auth-security-note"><i class="bi bi-shield-check"></i> For security, the same confirmation is shown for registered and unregistered addresses.</p>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/auth-login.js') . '?v=20261007-login') ?>"></script>
</body>
</html>

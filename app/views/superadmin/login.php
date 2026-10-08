<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Administration | StoneSoft Corevia</title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth-login.css') . '?v=20261007-login') ?>">
</head>
<body class="auth-page" style="--auth-accent:#4338ca;--auth-accent-dark:#3730a3;--auth-secondary:#0891b2">
<main class="auth-shell">
    <section class="auth-showcase" aria-labelledby="welcome-title">
        <div class="auth-brand">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="Stonesoft IT Solutions">
            <span>StoneSoft Corevia<small>Platform Administration</small></span>
        </div>

        <div class="auth-showcase-copy">
            <div class="auth-eyebrow">Platform operations</div>
            <h1 id="welcome-title">Control with confidence</h1>
            <p>Oversee tenants, subscriptions, security, billing and platform performance from the administration workspace.</p>
        </div>

        <img class="auth-art" src="<?= e(asset('assets/img/login-workspace.png')) ?>" alt="Secure platform operations and business management workspace">
        <p class="auth-showcase-footer">Authorised Stonesoft personnel only</p>
    </section>

    <section class="auth-form-panel" aria-labelledby="login-title">
        <div class="auth-form-wrap">
            <div class="auth-portal-label"><i class="bi bi-shield-lock"></i> Platform administration</div>
            <h2 id="login-title">Sign in to platform control</h2>
            <p class="auth-form-intro">Enter your authorised platform administrator credentials to continue.</p>

            <?php if (!empty($flashError)): ?>
                <div class="auth-alert auth-alert-danger" data-auth-alert><?= e((string)$flashError) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashSuccess)): ?>
                <div class="auth-alert auth-alert-success" data-auth-alert><?= e((string)$flashSuccess) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('superadmin/auth/loginStore')) ?>" data-auth-form>
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="auth-field">
                    <label class="auth-label" for="platform-email">Email address</label>
                    <input id="platform-email" type="email" name="email" class="auth-control" placeholder="administrator@stonesoftzambia.com" autocomplete="username" required autofocus>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="platform-password">Password</label>
                    <div class="auth-password">
                        <input id="platform-password" type="password" name="password" class="auth-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="#platform-password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="auth-form-meta"></div>
                <button type="submit" class="auth-submit"><i class="bi bi-shield-check"></i><span>Sign in to platform</span></button>
            </form>

            <div class="auth-divider">Company access</div>
            <a class="auth-other-link" href="<?= e(base_url('auth/login')) ?>"><i class="bi bi-building-lock"></i>Admin / HR sign in</a>
            <p class="auth-security-note"><i class="bi bi-exclamation-circle"></i> Access attempts are monitored for security and audit purposes.</p>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/auth-login.js') . '?v=20261007-login') ?>"></script>
</body>
</html>

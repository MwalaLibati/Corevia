<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Affiliate Portal | StoneSoft Corevia</title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth-login.css') . '?v=20261007-login') ?>">
</head>
<body class="auth-page" style="--auth-accent:#0f766e;--auth-accent-dark:#115e59;--auth-secondary:#2563eb">
<main class="auth-shell">
    <section class="auth-showcase" aria-labelledby="welcome-title">
        <div class="auth-brand">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="Stonesoft IT Solutions">
            <span>StoneSoft Corevia<small>Affiliate Network</small></span>
        </div>

        <div class="auth-showcase-copy">
            <div class="auth-eyebrow">Partner workspace</div>
            <h1 id="welcome-title">Grow with clarity</h1>
            <p>Follow referred companies, commission earnings, payout progress and partnership documents in one place.</p>
        </div>

        <img class="auth-art" src="<?= e(asset('assets/img/login-workspace.png')) ?>" alt="Partner performance, payments and business workspace">
        <p class="auth-showcase-footer">A Stonesoft IT Solutions partner experience</p>
    </section>

    <section class="auth-form-panel" aria-labelledby="login-title">
        <div class="auth-form-wrap">
            <div class="auth-portal-label"><i class="bi bi-diagram-3"></i> Affiliate portal</div>
            <h2 id="login-title">Sign in to your partner account</h2>
            <p class="auth-form-intro">Use your registered affiliate email address and account password.</p>

            <?php if (!empty($flashErr)): ?>
                <div class="auth-alert auth-alert-danger" data-auth-alert><?= e((string)$flashErr) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash)): ?>
                <div class="auth-alert auth-alert-success" data-auth-alert><?= e((string)$flash) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('affiliate/auth/loginStore')) ?>" data-auth-form>
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="auth-field">
                    <label class="auth-label" for="affiliate-email">Email address</label>
                    <input id="affiliate-email" type="email" name="email" class="auth-control" placeholder="name@example.com" autocomplete="username" required autofocus>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="affiliate-password">Password</label>
                    <div class="auth-password">
                        <input id="affiliate-password" type="password" name="password" class="auth-control" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" data-password-toggle="#affiliate-password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="auth-form-meta"></div>
                <button type="submit" class="auth-submit"><i class="bi bi-box-arrow-in-right"></i><span>Sign in to affiliate portal</span></button>
            </form>

            <p class="auth-security-note"><i class="bi bi-shield-check"></i> Affiliate access is restricted to approved partner accounts.</p>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/auth-login.js') . '?v=20261007-login') ?>"></script>
</body>
</html>

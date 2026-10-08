<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employee Portal | <?= e(app_product_name()) ?></title>
    <link rel="shortcut icon" href="<?= e(asset('assets/img/favicon.png')) ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/auth-login.css') . '?v=20261007-login') ?>">
</head>
<body class="auth-page" style="--auth-accent:#15803d;--auth-accent-dark:#166534;--auth-secondary:#0284c7">
<main class="auth-shell">
    <section class="auth-showcase" aria-labelledby="welcome-title">
        <div class="auth-brand">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="Stonesoft IT Solutions">
            <span>StoneSoft Corevia<small>Employee Self-Service</small></span>
        </div>

        <div class="auth-showcase-copy">
            <div class="auth-eyebrow">Your employee workspace</div>
            <h1 id="welcome-title">Your workday, in one place</h1>
            <p>Access payslips, contracts, schedules, attendance records and personal employment information securely.</p>
        </div>

        <img class="auth-art" src="<?= e(asset('assets/img/login-workspace.png')) ?>" alt="Employee payroll, calendar and document workspace">
        <p class="auth-showcase-footer">Powered by <?= e(app_vendor_name()) ?></p>
    </section>

    <section class="auth-form-panel" aria-labelledby="login-title">
        <div class="auth-form-wrap">
            <div class="auth-portal-label"><i class="bi bi-person-badge"></i> Employee portal</div>
            <h2 id="login-title">Sign in to self-service</h2>
            <p class="auth-form-intro">Use the company-prefixed employee number and password issued to you.</p>

            <?php if (!empty($flashError)): ?>
                <div class="auth-alert auth-alert-danger" data-auth-alert><?= e((string)$flashError) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashSuccess)): ?>
                <div class="auth-alert auth-alert-success" data-auth-alert><?= e((string)$flashSuccess) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('portal/loginStore')) ?>" data-auth-form>
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="auth-field">
                    <label class="auth-label" for="employee-number">Employee number</label>
                    <input id="employee-number" type="text" name="employee_number" class="auth-control" placeholder="For example MDS0001" required autofocus autocomplete="username" maxlength="10" pattern="[A-Za-z]{2,6}[0-9]{4}" title="Enter the company code followed by four digits, for example MDS0001" data-uppercase>
                </div>

                <div class="auth-field">
                    <label class="auth-label" for="employee-password">Password</label>
                    <div class="auth-password">
                        <input id="employee-password" type="password" name="password" class="auth-control" placeholder="Enter your password" required autocomplete="current-password">
                        <button class="auth-password-toggle" type="button" data-password-toggle="#employee-password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="auth-form-meta"></div>
                <button type="submit" class="auth-submit"><i class="bi bi-box-arrow-in-right"></i><span>Sign in to my portal</span></button>
            </form>

            <div class="auth-divider">Company access</div>
            <a class="auth-other-link" href="<?= e(base_url('auth/login')) ?>"><i class="bi bi-building-lock"></i>Admin / HR sign in</a>
            <p class="auth-security-note"><i class="bi bi-info-circle"></i> Contact your HR team if you have not received your login details.</p>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= e(asset('assets/js/auth-login.js') . '?v=20261007-login') ?>"></script>
</body>
</html>

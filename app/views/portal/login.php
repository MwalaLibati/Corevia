<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal Login | <?= e(app_product_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/portal.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      body { background: linear-gradient(135deg, #14532d 0%, #166534 50%, #15803d 100%) !important; }
      .portal-login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
      .portal-login-card { background: #fff; border-radius: 12px; box-shadow: 0 8px 40px rgba(0,0,0,.25); padding: 44px 40px; width: 100%; max-width: 400px; }
      .portal-login-logo { text-align: center; margin-bottom: 24px; }
      .portal-login-logo img { max-height: 64px; }
      .portal-login-title { font-size: 1.3rem; font-weight: 700; color: #14532d; margin-bottom: 4px; }
      .portal-login-sub   { font-size: .82rem; color: #6b7280; margin-bottom: 28px; }
      .portal-login-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: 4px; }
      .portal-admin-link  { font-size: .78rem; color: #9ca3af; text-decoration: none; }
      .portal-admin-link:hover { color: #166534; }
    </style>
</head>
<body>
<div class="portal-login-wrap">
    <div class="portal-login-card">
        <div class="portal-login-logo">
            <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>">
        </div>
        <div class="text-center">
            <div class="portal-login-title">Employee Self-Service</div>
            <div class="portal-login-sub" style="margin-bottom:8px"><?= e(app_vendor_name()) ?></div>
            <div class="portal-login-sub">Sign in with your employee number to view your payslips, profile and contracts.</div>
        </div>

        <?php if (!empty($flashError)): ?>
            <div class="portal-alert-error" style="display:none"><?= e((string)$flashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashSuccess)): ?>
            <div class="portal-alert-success" style="display:none"><?= e((string)$flashSuccess) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('portal/loginStore')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="mb-3">
                <div class="portal-login-label">Employee Number</div>
                <input type="text" name="employee_number" class="form-control" placeholder="e.g. EMP-0001" required autofocus autocomplete="username" style="text-transform:uppercase">
            </div>
            <div class="mb-4">
                <div class="portal-login-label">Password</div>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn w-100 fw-bold text-white" style="background:var(--portal-green);padding:10px">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="<?= e(base_url('auth/login')) ?>" class="portal-admin-link">
                <i class="bi bi-shield-lock me-1"></i> Admin / HR Login
            </a>
        </div>
    </div>
</div>

<script src="<?= e(asset('assets/js/jquery-3.6.0.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
if (typeof Swal !== 'undefined') {
    var _t = Swal.mixin({ toast:true, position:'top-end', showConfirmButton:false, timer:4500, timerProgressBar:true });
    document.querySelectorAll('.portal-alert-error').forEach(function(el){ el.style.display='none'; _t.fire({ icon:'error', title:el.innerText.trim() }); });
    document.querySelectorAll('.portal-alert-success').forEach(function(el){ el.style.display='none'; _t.fire({ icon:'success', title:el.innerText.trim() }); });
}
document.querySelector('input[name=employee_number]').addEventListener('input', function(){
    this.value = this.value.toUpperCase();
});
</script>
</body>
</html>

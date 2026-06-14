<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Reset Password | <?= e(app_product_name()) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
</head>
<body class="bg-primary">
<div class="row align-items-center justify-content-center vh-100">
    <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-6">
        <div class="card rounded-2 border-0 p-5 m-0">
            <div class="card-header border-0 p-0 text-center mb-4">
                <a href="<?= e(base_url('auth/login')) ?>" class="w-100 d-inline-block mb-4">
                    <img src="<?= e(asset('assets/img/Logo.png')) ?>" alt="<?= e(app_product_name()) ?>">
                </a>
                <h3>Set New Password</h3>
                <p class="fs-14 text-muted my-3">Choose a strong password with at least 8 characters.</p>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($flashError)): ?>
                    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(base_url('auth/resetPasswordStore')) ?>">
                    <input type="hidden" name="_csrf"  value="<?= e((string) $csrf) ?>">
                    <input type="hidden" name="token"  value="<?= e((string) $token) ?>">
                    <div class="form-group mb-3">
                        <input type="password" class="form-control" name="password" placeholder="New password (min 8 chars)" minlength="8" required autofocus>
                    </div>
                    <div class="form-group mb-3">
                        <input type="password" class="form-control" name="password_confirm" placeholder="Confirm new password" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 text-uppercase text-white rounded-2 lh-34 fw-bold shadow">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="<?= e(asset('assets/js/jquery-3.6.0.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.alert-danger').forEach(function(el){
    el.style.display = 'none';
    Swal.fire({ toast:true, position:'top-end', icon:'error', title:el.innerText.trim(), showConfirmButton:false, timer:5000, timerProgressBar:true });
});
</script>
</body>
</html>

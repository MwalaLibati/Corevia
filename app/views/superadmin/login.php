<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Admin Login</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/enterprise.css')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#4c1d95 100%);min-height:100vh;display:flex;align-items:center;justify-content:center">
<div style="width:100%;max-width:400px;padding:16px">
    <div class="card border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
        <div style="background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:28px 32px 20px;text-align:center">
            <div style="width:52px;height:52px;background:rgba(255,255,255,.2);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
                <i class="bi bi-shield-lock-fill text-white" style="font-size:1.4rem"></i>
            </div>
            <h4 class="text-white mb-1 fw-bold">Platform Admin</h4>
            <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.82rem">Stonesoft Super Admin Portal</p>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($flashError)): ?>
            <div class="alert alert-danger py-2 mb-3" style="font-size:.83rem"><?= e($flashError) ?></div>
            <?php endif; ?>
            <?php if (!empty($flashSuccess)): ?>
            <div class="alert alert-success py-2 mb-3" style="font-size:.83rem"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url('superadmin/auth/loginStore')) ?>">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.83rem">Email Address</label>
                    <input type="email" name="email" class="form-control" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:.83rem">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn w-100 text-white fw-semibold" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:10px">
                    <i class="bi bi-shield-check me-2"></i>Sign In to Platform
                </button>
            </form>
        </div>
    </div>
    <p class="text-center mt-3" style="color:rgba(255,255,255,.5);font-size:.74rem">Restricted access - authorised personnel only</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
if (typeof Swal !== 'undefined') {
    document.querySelectorAll('.alert-danger, .alert-success').forEach(function(el) {
        var text = el.innerText.trim();
        if (!text) return;
        var icon = el.classList.contains('alert-danger') ? 'error' : 'success';
        el.style.display = 'none';
        Swal.fire({toast:true,position:'top-end',icon:icon,title:text,showConfirmButton:false,timer:4500,timerProgressBar:true});
    });
}
</script>
</body>
</html>

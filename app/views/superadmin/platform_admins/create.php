<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="text-dark mb-0 fw-bold">Create Platform Admin</h2>
    <a href="<?= e(base_url('superadmin/platform-admin/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-danger"><?= e((string) $flash) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:640px">
    <div class="card-body p-4">
        <form method="post" action="<?= e(base_url('superadmin/platform-admin/store')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <button type="submit" class="btn text-white" style="background:#7c3aed">Create Admin</button>
        </form>
    </div>
</div>

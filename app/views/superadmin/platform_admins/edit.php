<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="text-dark mb-0 fw-bold">Edit Platform Admin</h2>
    <a href="<?= e(base_url('superadmin/platform-admin/index')) ?>" class="btn btn-sm btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-danger"><?= e((string) $flash) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="alert alert-success"><?= e((string) $success) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:640px">
    <div class="card-body p-4">
        <form method="post" action="<?= e(base_url('superadmin/platform-admin/update')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= e((string) $admin['full_name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e((string) $admin['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
            </div>
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1" <?= (int) $admin['is_active'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="isActive">Active</label>
            </div>
            <button type="submit" class="btn text-white" style="background:#7c3aed">Save Admin</button>
        </form>
    </div>
</div>

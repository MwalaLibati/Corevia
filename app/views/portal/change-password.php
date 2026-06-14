<div class="portal-page-header">
    <h2><i class="bi bi-key me-2"></i>Change Password</h2>
    <p>Update your employee portal password.</p>
</div>

<?php if (!empty($flashError)): ?>
    <div class="portal-alert-error" style="display:none"><?= e((string)$flashError) ?></div>
<?php endif; ?>

<div class="portal-card" style="max-width:480px">
    <form method="post" action="<?= e(base_url('portal/changePasswordStore')) ?>">
        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold">Current Password</label>
            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">New Password <small class="text-muted fw-normal">(min 8 characters)</small></label>
            <input type="password" name="new_password" class="form-control" minlength="8" required autocomplete="new-password">
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-success px-4">
            <i class="bi bi-check-circle me-1"></i> Update Password
        </button>
        <a href="<?= e(base_url('portal/dashboard')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
    </form>
</div>

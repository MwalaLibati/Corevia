<?php
$isEdit = !empty($user);
$formData = !empty($old) ? $old : ($user ?? []);
$postUrl = $isEdit ? base_url('user-management/update/' . (string) $user['id']) : base_url('user-management/store');
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark"><?= $isEdit ? 'Edit User' : 'Create User' ?></h2>
        <p class="text-gray mb-0">Grant a user access to this company and assign their role.</p>
    </div>
    <a href="<?= e(base_url('user-management/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($postUrl) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e((string) ($formData['full_name'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e((string) ($formData['email'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label"><?= $isEdit ? 'New Password' : 'Password *' ?></label>
                <input type="password" name="password" class="form-control" minlength="8" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                <?php if ($isEdit): ?>
                    <small class="text-gray">Leave blank to keep the current password.</small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">Role *</label>
                <?php $selectedRole = (int) ($formData['role_id'] ?? 0); ?>
                <select name="role_id" class="form-select" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= $selectedRole === (int) $role['id'] ? 'selected' : '' ?>>
                            <?= e((string) $role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <?php $isActive = (int) ($formData['is_active'] ?? 1); ?>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="isActive" <?= $isActive === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Login active</label>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div class="col-md-4">
                <?php $membershipActive = (int) ($formData['membership_active'] ?? 1); ?>
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" name="membership_active" value="1" id="membershipActive" <?= $membershipActive === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="membershipActive">Company access active</label>
                </div>
            </div>
            <?php endif; ?>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create User' ?></button>
                <a href="<?= e(base_url('user-management/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

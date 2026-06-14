<?php $oldInput = !empty($old) ? $old : $setting; ?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Setting</h2>
        <p class="text-gray mb-0">Update system configuration key and value.</p>
    </div>
    <a href="<?= e(base_url('settings/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('settings/update/' . (string) $setting['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Setting Key *</label>
                <input type="text" name="setting_key" class="form-control" value="<?= e((string) ($oldInput['setting_key'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Setting Value</label>
                <input type="text" name="setting_value" class="form-control" value="<?= e((string) ($oldInput['setting_value'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update Setting</button>
                <a href="<?= e(base_url('settings/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

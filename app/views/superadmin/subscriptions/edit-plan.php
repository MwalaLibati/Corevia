<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold"><?= e((string) $plan['name']) ?> Plan</h2>
        <p class="text-muted mb-0 mt-1 small">Set the default price and included modules for this plan.</p>
    </div>
    <a href="<?= e(base_url('superadmin/subscription/plans')) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-danger"><?= e((string) $flash) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
<div class="alert alert-success"><?= e((string) $success) ?></div>
<?php endif; ?>

<?php
$selected = array_flip($selectedModules ?? []);
$sections = [];
foreach (($modules ?? []) as $key => $module) {
    $sections[(string) $module['section']][$key] = $module;
}
?>

<form method="post" action="<?= e(base_url('superadmin/subscription/updatePlan')) ?>">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="3" class="form-control"><?= e((string) ($plan['description'] ?? '')) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Monthly Rate</label>
                        <div class="input-group">
                            <span class="input-group-text"><?= e((string) $plan['currency']) ?></span>
                            <input type="number" step="0.01" min="0" name="default_monthly_rate" class="form-control" value="<?= e(number_format((float) $plan['default_monthly_rate'], 2, '.', '')) ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Billing Cycle</label>
                        <select name="default_billing_cycle" class="form-select">
                            <?php foreach (['Monthly','Annual'] as $cycle): ?>
                            <option value="<?= $cycle ?>" <?= (string) $plan['default_billing_cycle'] === $cycle ? 'selected' : '' ?>><?= $cycle ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="isActive" <?= (int) $plan['is_active'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isActive">Active plan</label>
                    </div>
                    <button type="submit" class="btn text-white w-100" style="background:#7c3aed">
                        <i class="bi bi-floppy me-1"></i>Save Plan
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Included Modules</h5>
                    <div class="row g-3">
                        <?php foreach ($sections as $section => $items): ?>
                        <div class="col-md-6">
                            <div class="border rounded-2 p-3 h-100">
                                <div class="fw-semibold mb-2"><?= e($section) ?></div>
                                <?php foreach ($items as $key => $module): ?>
                                <label class="d-flex align-items-center gap-2 py-1" style="font-size:.92rem">
                                    <input type="checkbox" name="modules[]" value="<?= e((string) $key) ?>" <?= isset($selected[$key]) ? 'checked' : '' ?>>
                                    <i class="bi <?= e((string) $module['icon']) ?> text-muted"></i>
                                    <span><?= e((string) $module['label']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

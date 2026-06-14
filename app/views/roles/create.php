<?php
$isEdit = !empty($role);
$formData = !empty($old) ? $old : ($role ?? []);
$postUrl = $isEdit ? base_url('role/update/' . (string) $role['id']) : base_url('role/store');
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark"><?= $isEdit ? 'Edit Role' : 'Create Role' ?></h2>
        <p class="text-gray mb-0">The access profile controls what this role can do in the system.</p>
    </div>
    <a href="<?= e(base_url('role/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <style>
            .role-module-panel {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #fff;
                padding: 16px;
                height: 100%;
            }
            .role-module-panel-title {
                color: #111827;
                font-weight: 700;
                margin-bottom: 10px;
            }
            .role-module-panel .form-check {
                display: flex;
                align-items: center;
                gap: 8px;
                min-height: 30px;
                margin-bottom: 8px;
            }
            .role-module-panel .form-check-input {
                flex: 0 0 auto;
                margin: 0;
            }
            .role-module-panel .form-check-label {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin: 0;
            }
        </style>
        <form method="post" action="<?= e($postUrl) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Role Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e((string) ($formData['name'] ?? '')) ?>" placeholder="Payroll Manager" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Access Profile *</label>
                <?php $selectedAccess = (string) ($formData['access_level'] ?? 'Viewer'); ?>
                <select name="access_level" class="form-select" required>
                    <?php foreach ($accessLevels as $level): ?>
                        <option value="<?= e($level) ?>" <?= $selectedAccess === $level ? 'selected' : '' ?>>
                            <?= e($level) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= e((string) ($formData['description'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Visible Modules</label>
                <p class="text-gray small mb-3">Choose which menu modules this role can see and open.</p>

                <?php
                    $selectedSet = array_flip(array_map('strval', $selectedModules ?? []));
                    $bySection = [];
                    foreach (($modules ?? []) as $key => $module) {
                        $bySection[(string) $module['section']][$key] = $module;
                    }
                ?>

                <div class="row g-3">
                    <?php foreach ($bySection as $section => $items): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="role-module-panel">
                                <div class="role-module-panel-title"><?= e($section) ?></div>
                                <?php foreach ($items as $key => $module): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="modules[]"
                                               value="<?= e((string) $key) ?>" id="module_<?= e((string) $key) ?>"
                                               <?= isset($selectedSet[(string) $key]) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="module_<?= e((string) $key) ?>">
                                            <i class="bi <?= e((string) $module['icon']) ?> me-1 text-muted"></i>
                                            <?= e((string) $module['label']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Save Role' ?></button>
                <a href="<?= e(base_url('role/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

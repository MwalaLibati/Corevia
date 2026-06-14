<?php $oldInput = !empty($old) ? $old : $type; ?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Deduction Type</h2>
        <p class="text-gray mb-0">Update deduction configuration.</p>
    </div>
    <a href="<?= e(base_url('deduction/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('deduction/update/' . (string) $type['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e((string) ($oldInput['name'] ?? '')) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Code</label>
                <input type="text" name="code" class="form-control" value="<?= e((string) ($oldInput['code'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <?php $calcType = (string) ($oldInput['calculation_type'] ?? 'Fixed'); ?>
                <label class="form-label">Calculation Type</label>
                <select name="calculation_type" class="form-select">
                    <option value="Fixed" <?= $calcType === 'Fixed' ? 'selected' : '' ?>>Fixed</option>
                    <option value="Percent" <?= $calcType === 'Percent' ? 'selected' : '' ?>>Percent</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Default Value</label>
                <input type="number" step="0.01" min="0" name="default_value" class="form-control" value="<?= e((string) ($oldInput['default_value'] ?? '0')) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <?php $isStatutory = (int) ($oldInput['is_statutory'] ?? 0); ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_statutory" value="1" id="isStatutory" <?= $isStatutory === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isStatutory">Statutory</label>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <?php $autoApply = (int) ($oldInput['auto_apply'] ?? 0); ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="auto_apply" value="1" id="autoApply" <?= $autoApply === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="autoApply">Auto-apply <i class="bi bi-info-circle text-muted" title="Automatically deducted for all active employees each payroll run."></i></label>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <?php $isActive = (int) ($oldInput['is_active'] ?? 1); ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" <?= $isActive === 1 ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
            </div>

            <?php if (in_array((string)($type['code'] ?? ''), ['PAYE','NAPSA','NHIMA'], true)): ?>
            <div class="col-12">
                <div class="alert alert-info py-2 mb-0" style="font-size:.82rem">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Statutory deduction.</strong>
                    <?php if ((string)($type['code'] ?? '') === 'PAYE'): ?>
                        PAYE rates are managed in the <strong>tax_bands</strong> table. The <em>Default Value</em> field is not used for PAYE - disable this deduction to suppress PAYE from payroll runs.
                    <?php else: ?>
                        Edit <em>Default Value</em> to change the <?= e((string)$type['code']) ?> employee rate (%). Disable to exclude from all payroll calculations.
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update Deduction Type</button>
                <a href="<?= e(base_url('deduction/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

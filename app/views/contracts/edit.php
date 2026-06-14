<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Contract</h2>
        <p class="text-gray mb-0">
            <?= e((string) ($contract['employee_name'] ?? '')) ?>
            &mdash; <?= e((string) ($contract['contract_number'] ?? '')) ?>
        </p>
    </div>
    <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('contract/update/' . (string) $contract['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Contract Number</label>
                <input type="text" class="form-control" value="<?= e((string) ($contract['contract_number'] ?? '')) ?>" readonly>
            </div>

            <div class="col-md-8">
                <label class="form-label">Employee</label>
                <input type="text" class="form-control" value="<?= e((string) ($contract['employee_number'] ?? '') . ' — ' . (string) ($contract['employee_name'] ?? '')) ?>" readonly>
            </div>

            <div class="col-md-4">
                <label class="form-label">Contract Type *</label>
                <select name="contract_type" class="form-select" required>
                    <?php foreach ($contractTypes as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($formData['contract_type'] ?? '') === $type) ? 'selected' : '' ?>>
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Start Date *</label>
                <input type="date" name="start_date" class="form-control" value="<?= e((string) ($formData['start_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date <span class="text-gray">(blank = no expiry)</span></label>
                <input type="date" name="end_date" class="form-control" value="<?= e((string) ($formData['end_date'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <?php foreach ($contractStatuses as $s): ?>
                        <option value="<?= e($s) ?>" <?= (($formData['status'] ?? '') === $s) ? 'selected' : '' ?>>
                            <?= e($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                <a href="<?= e(base_url('contract/download/' . (string) $contract['id'])) ?>" class="btn btn-outline-dark ms-2" target="_blank">&#8615; Download Contract</a>
                <?php if (($contract['status'] ?? '') === 'Active'): ?>
                    <a href="<?= e(base_url('contract/renew/' . (string) $contract['id'])) ?>" class="btn btn-outline-success ms-2">Renew</a>
                    <form method="post" action="<?= e(base_url('contract/terminate/' . (string) $contract['id'])) ?>" class="d-inline ms-2">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Terminate this contract?');">Terminate</button>
                    </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

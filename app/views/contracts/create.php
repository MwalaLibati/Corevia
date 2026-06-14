<?php
$oldInput = !empty($old) ? $old : [];
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">New Contract</h2>
        <p class="text-gray mb-0">Create an employment contract for a staff member.</p>
    </div>
    <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('contract/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-4">
                <label class="form-label">Contract Number</label>
                <input type="text" class="form-control" value="<?= e((string) ($nextContractNumber ?? '')) ?>" readonly>
                <small class="text-gray">Auto-generated on save.</small>
            </div>

            <div class="col-md-8">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select employee</option>
                    <?php foreach ($employees as $emp): ?>
                        <?php
                            $selectedId = (string) ($oldInput['employee_id'] ?? $preEmployeeId ?? '');
                            $isSelected = $selectedId === (string) $emp['id'];
                        ?>
                        <option value="<?= e((string) $emp['id']) ?>" <?= $isSelected ? 'selected' : '' ?>>
                            <?= e((string) $emp['employee_number']) ?> &mdash; <?= e((string) $emp['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Contract Type *</label>
                <select name="contract_type" class="form-select" required>
                    <?php foreach ($contractTypes as $type): ?>
                        <option value="<?= e($type) ?>" <?= (($oldInput['contract_type'] ?? 'Contract') === $type) ? 'selected' : '' ?>>
                            <?= e($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Start Date *</label>
                <input type="date" name="start_date" class="form-control" value="<?= e((string) ($oldInput['start_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date <span class="text-gray">(leave blank for permanent)</span></label>
                <input type="date" name="end_date" class="form-control" value="<?= e((string) ($oldInput['end_date'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this contract..."><?= e((string) ($oldInput['notes'] ?? '')) ?></textarea>
            </div>

            <!-- Template auto-match notice -->
            <?php if (!empty($templates)): ?>
            <div class="col-12">
                <div id="templateNotice" class="alert alert-info d-flex gap-2 align-items-center py-2" style="font-size:.84rem">
                    <i class="bi bi-file-earmark-text flex-shrink-0"></i>
                    <span id="templateNoticeText">
                        A contract document template will be auto-selected based on the employee&rsquo;s salary structure and contract type.
                    </span>
                </div>
            </div>
            <?php else: ?>
            <div class="col-12">
                <div class="alert alert-warning d-flex gap-2 align-items-center py-2" style="font-size:.84rem">
                    <i class="bi bi-exclamation-triangle flex-shrink-0"></i>
                    No contract templates configured yet.
                    <a href="<?= e(base_url('contract_template/create')) ?>" class="ms-1">Create a template</a> to generate dynamic contract documents.
                </div>
            </div>
            <?php endif; ?>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Create Contract</button>
                <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

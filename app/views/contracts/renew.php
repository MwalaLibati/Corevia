<?php
$oldInput = !empty($old) ? $old : [];
$currentEndDate = (string) ($contract['end_date'] ?? '');
$defaultStartDate = $currentEndDate !== ''
    ? date('Y-m-d', strtotime($currentEndDate . ' +1 day'))
    : date('Y-m-d');
$requestedEndDate = (string) (($pendingRequest ?? [])['requested_end_date'] ?? '');
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Renew Contract</h2>
        <p class="text-gray mb-0">
            <?= e((string) ($contract['employee_name'] ?? '')) ?>
            &mdash; current contract: <strong><?= e((string) ($contract['contract_number'] ?? '')) ?></strong>
        </p>
    </div>
    <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php if (!empty($pendingRequest)): ?>
    <div class="alert alert-info">
        <strong>Employee renewal request:</strong>
        submitted on <?= e(date('d M Y', strtotime((string)$pendingRequest['created_at']))) ?>.
        <?php if (!empty($pendingRequest['requested_end_date'])): ?>
            Preferred new end date: <strong><?= e((string)$pendingRequest['requested_end_date']) ?></strong>.
        <?php endif; ?>
        <?php if (trim((string)($pendingRequest['reason'] ?? '')) !== ''): ?>
            <div class="mt-1"><?= e((string)$pendingRequest['reason']) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-gray mb-3">Current Contract Details</h6>
                <table class="table table-sm mb-0">
                    <tr><th class="text-gray fw-normal">Contract No.</th><td><?= e((string) ($contract['contract_number'] ?? '-')) ?></td></tr>
                    <tr><th class="text-gray fw-normal">Type</th><td><?= e((string) ($contract['contract_type'] ?? '-')) ?></td></tr>
                    <tr><th class="text-gray fw-normal">Start Date</th><td><?= e((string) ($contract['start_date'] ?? '-')) ?></td></tr>
                    <tr><th class="text-gray fw-normal">End Date</th><td><?= !empty($contract['end_date']) ? e((string) $contract['end_date']) : '<span class="text-gray">No expiry</span>' ?></td></tr>
                    <tr><th class="text-gray fw-normal">Status</th><td><?= e((string) ($contract['status'] ?? '-')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="text-gray mb-3">New Contract Details <span class="badge bg-success-subtle text-success-emphasis ms-1">Next: <?= e((string) ($nextContractNumber ?? '')) ?></span></h6>
                <form method="post" action="<?= e(base_url('contract/renewStore/' . (string) $contract['id'])) ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

                    <div class="col-12">
                        <label class="form-label">Contract Type *</label>
                        <select name="contract_type" class="form-select" required>
                            <?php foreach ($contractTypes as $type): ?>
                                <option value="<?= e($type) ?>" <?= (($oldInput['contract_type'] ?? $contract['contract_type'] ?? '') === $type) ? 'selected' : '' ?>>
                                    <?= e($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">New Start Date *</label>
                        <input type="date" name="start_date" class="form-control"
                               value="<?= e((string) ($oldInput['start_date'] ?? $defaultStartDate)) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">New End Date <span class="text-gray">(blank = no expiry)</span></label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?= e((string) ($oldInput['end_date'] ?? $requestedEndDate)) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional renewal notes..."><?= e((string) ($oldInput['notes'] ?? (($pendingRequest ?? [])['reason'] ?? ''))) ?></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Confirm Renewal</button>
                        <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

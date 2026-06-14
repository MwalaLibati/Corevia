<?php $oldInput = !empty($old) ? $old : $structure; ?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Salary Structure</h2>
        <p class="text-gray mb-0">Update base pay and allowances.</p>
    </div>
    <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('salary/update/' . (string) $structure['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="<?= e((string) ($oldInput['name'] ?? '')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Grade Level</label>
                <input type="text" name="grade_level" class="form-control" value="<?= e((string) ($oldInput['grade_level'] ?? '')) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Basic Pay</label>
                <input type="number" step="0.01" min="0" name="basic_pay" class="form-control" value="<?= e((string) ($oldInput['basic_pay'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Housing</label>
                <input type="number" step="0.01" min="0" name="housing_allowance" class="form-control" value="<?= e((string) ($oldInput['housing_allowance'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Transport</label>
                <input type="number" step="0.01" min="0" name="transport_allowance" class="form-control" value="<?= e((string) ($oldInput['transport_allowance'] ?? '0')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Other</label>
                <input type="number" step="0.01" min="0" name="other_allowances" class="form-control" value="<?= e((string) ($oldInput['other_allowances'] ?? '0')) ?>">
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update Structure</button>
                <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

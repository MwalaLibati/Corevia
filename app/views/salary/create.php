<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div><h2 class="text-dark">Create Salary Structure</h2><p class="text-gray mb-0">Set the basic pay and select only the allowances this structure needs.</p></div>
    <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>
<form method="post" action="<?= e(base_url('salary/store')) ?>">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3">
        <div class="col-md-5"><label class="form-label">Structure Name *</label><input type="text" name="name" class="form-control" value="<?= e((string) ($old['name'] ?? '')) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Grade Level</label><input type="text" name="grade_level" class="form-control" value="<?= e((string) ($old['grade_level'] ?? '')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Basic Pay</label><input type="number" step="0.01" min="0" name="basic_pay" class="form-control" value="<?= e((string) ($old['basic_pay'] ?? '0')) ?>"></div>
    </div></div></div>
    <div class="d-flex align-items-center justify-content-between mb-3"><div><h5 class="mb-1">Applicable Allowances</h5><p class="text-gray mb-0">Tick an allowance to include it in this salary structure.</p></div><a href="<?= e(base_url('allowance/index')) ?>" class="btn btn-outline-primary">Manage Allowance Types</a></div>
    <div class="card border-0 shadow-sm mb-4"><div class="card-body">
        <?php if (empty($allowanceTypes)): ?><div class="text-center py-4"><p class="text-gray">No allowance types have been created yet.</p><a href="<?= e(base_url('allowance/index')) ?>" class="btn btn-primary">Create Allowance Type</a></div>
        <?php else: ?><div class="row g-3"><?php foreach ($allowanceTypes as $allowance): $aid=(int)$allowance['id']; $checked=array_key_exists($aid,$selectedAllowances??[]); ?>
            <div class="col-lg-6"><div class="border rounded p-3 h-100"><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="allowance_ids[]" value="<?= $aid ?>" id="allowance-<?= $aid ?>" <?= $checked?'checked':'' ?>><label class="form-check-label fw-semibold" for="allowance-<?= $aid ?>"><?= e((string)$allowance['name']) ?> <span class="text-gray fw-normal">(<?= e((string)$allowance['code']) ?>)</span></label></div><label class="form-label small"><?= $allowance['calculation_type']==='Percent'?'Percentage of basic pay':'Amount' ?></label><div class="input-group"><input type="number" step="0.01" min="0" name="allowance_amount[<?= $aid ?>]" class="form-control" value="<?= e((string)($selectedAllowances[$aid]??$allowance['default_value'])) ?>"><span class="input-group-text"><?= $allowance['calculation_type']==='Percent'?'%':'ZMW' ?></span></div><div class="small text-gray mt-2"><?= !empty($allowance['is_taxable'])?'Taxable':'Non-taxable' ?> · <?= !empty($allowance['included_in_gross'])?'Included in gross':'Excluded from gross' ?></div></div></div>
        <?php endforeach; ?></div><?php endif; ?>
    </div></div>
    <button type="submit" class="btn btn-primary">Save Structure</button> <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
</form>

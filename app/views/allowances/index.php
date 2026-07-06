<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div><h2 class="text-dark">Allowance Types</h2><p class="text-gray mb-0">Create reusable allowances and control how payroll treats them.</p></div>
    <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary">Salary Structures</a>
</div>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <h5 class="mb-3">New Allowance</h5>
    <form method="post" action="<?= e(base_url('allowance/store')) ?>" class="row g-3">
        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
        <div class="col-md-4"><label class="form-label">Allowance Name *</label><input name="name" class="form-control" placeholder="e.g. Meal Allowance" required></div>
        <div class="col-md-2"><label class="form-label">Code *</label><input name="code" class="form-control" placeholder="MEAL" maxlength="30" required></div>
        <div class="col-md-3"><label class="form-label">Calculation</label><select name="calculation_type" class="form-select"><option value="Fixed">Fixed amount</option><option value="Percent">Percentage of basic pay</option></select></div>
        <div class="col-md-3"><label class="form-label">Default Value</label><input name="default_value" type="number" step="0.01" min="0" class="form-control" value="0"></div>
        <div class="col-12 d-flex flex-wrap gap-3"><?php foreach (['included_in_gross'=>'Include in gross pay','is_taxable'=>'Taxable','included_in_napsa'=>'Include in NAPSA base','included_in_nhima'=>'Include in NHIMA base','is_recurring'=>'Recurring'] as $name=>$label): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="<?= $name ?>" value="1" <?= in_array($name,['included_in_gross','is_taxable','included_in_nhima','is_recurring'],true)?'checked':'' ?>><span class="form-check-label"><?= e($label) ?></span></label><?php endforeach; ?></div>
        <div class="col-12"><button class="btn btn-primary">Create Allowance</button></div>
    </form>
</div></div>

<div class="card border-0 shadow-sm"><div class="card-body table-responsive">
    <table class="table table-striped align-middle"><thead><tr><th>Allowance</th><th>Calculation</th><th>Default</th><th>Payroll Treatment</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>
    <?php if (empty($allowances)): ?><tr><td colspan="6" class="text-center text-gray py-4">No allowance types have been created.</td></tr><?php endif; ?>
    <?php foreach (($allowances??[]) as $allowance): $id=(int)$allowance['id']; $formId='allowance-form-'.$id; ?>
        <tr>
            <td><form id="<?= $formId ?>" method="post" action="<?= e(base_url('allowance/update/'.$id)) ?>"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><input type="hidden" name="is_active" value="<?= e((string)$allowance['is_active']) ?>"></form><input form="<?= $formId ?>" name="name" class="form-control mb-1" value="<?= e((string)$allowance['name']) ?>" required><input form="<?= $formId ?>" name="code" class="form-control form-control-sm" value="<?= e((string)$allowance['code']) ?>" required></td>
            <td><select form="<?= $formId ?>" name="calculation_type" class="form-select"><option value="Fixed" <?= $allowance['calculation_type']==='Fixed'?'selected':'' ?>>Fixed</option><option value="Percent" <?= $allowance['calculation_type']==='Percent'?'selected':'' ?>>Percent</option></select></td>
            <td><input form="<?= $formId ?>" name="default_value" type="number" step="0.01" min="0" class="form-control" value="<?= e((string)$allowance['default_value']) ?>"></td>
            <td><?php foreach (['included_in_gross'=>'Gross','is_taxable'=>'Taxable','included_in_napsa'=>'NAPSA','included_in_nhima'=>'NHIMA','is_recurring'=>'Recurring'] as $name=>$label): ?><label class="form-check form-check-inline"><input form="<?= $formId ?>" class="form-check-input" type="checkbox" name="<?= $name ?>" value="1" <?= !empty($allowance[$name])?'checked':'' ?>><span class="form-check-label small"><?= e($label) ?></span></label><?php endforeach; ?></td>
            <td><?= !empty($allowance['is_active'])?'<span class="badge bg-success">Active</span>':'<span class="badge bg-secondary">Inactive</span>' ?></td>
            <td class="text-end"><button form="<?= $formId ?>" class="btn btn-sm btn-outline-primary">Save</button><?php if(!empty($allowance['is_active'])): ?><form method="post" action="<?= e(base_url('allowance/delete/'.$id)) ?>" class="d-inline"><input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>"><button class="btn btn-sm btn-outline-danger">Deactivate</button></form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>

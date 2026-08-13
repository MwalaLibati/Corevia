<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div><h2 class="text-dark">Create Salary Structure</h2><p class="text-gray mb-0">Set the basic pay and select only the allowances this structure needs.</p></div>
    <a href="<?= e(base_url('salary/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>
<?php
$paySource = (string) ($old['basic_pay_source'] ?? 'Fixed Salary');
$payOptions = [
    'Fixed Salary' => ['title' => 'Fixed salary', 'description' => 'Use the monthly basic pay amount.'],
    'Attendance Hours' => ['title' => 'Hours worked', 'description' => 'Basic pay is approved hours multiplied by hourly rate.'],
    'Days Worked' => ['title' => 'Days worked', 'description' => 'Basic pay is payable days multiplied by daily rate.'],
    'Shifts Worked' => ['title' => 'Shifts worked', 'description' => 'Basic pay is approved shifts multiplied by shift rate.'],
];
?>
<form method="post" action="<?= e(base_url('salary/store')) ?>">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
    <input type="hidden" name="basic_pay_source" id="salaryPaySourceInput" value="<?= e($paySource) ?>">
    <div class="card border-0 shadow-sm mb-4"><div class="card-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Structure Name *</label><input type="text" name="name" class="form-control" value="<?= e((string) ($old['name'] ?? '')) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Grade Level</label><input type="text" name="grade_level" class="form-control" value="<?= e((string) ($old['grade_level'] ?? '')) ?>"></div>
        <div class="col-12">
            <label class="form-label">How should basic pay be calculated?</label>
            <div class="row g-3">
                <?php foreach ($payOptions as $value => $option): ?>
                    <div class="col-md-3">
                        <button type="button" class="btn w-100 h-100 text-start border salary-pay-option <?= $paySource === $value ? 'btn-primary text-white' : 'btn-light' ?>" data-pay-source="<?= e($value) ?>">
                            <span class="fw-semibold d-block"><?= e($option['title']) ?></span>
                            <span class="small <?= $paySource === $value ? 'text-white-50' : 'text-gray' ?>"><?= e($option['description']) ?></span>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3 salary-pay-field" data-pay-field="Fixed Salary"><label class="form-label">Monthly Basic Pay</label><input type="number" step="0.01" min="0" name="basic_pay" class="form-control" value="<?= e((string) ($old['basic_pay'] ?? '0')) ?>"></div>
        <div class="col-md-3 salary-pay-field" data-pay-field="Attendance Hours"><label class="form-label">Hourly Rate</label><input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" value="<?= e((string) ($old['hourly_rate'] ?? '')) ?>"></div>
        <div class="col-md-3 salary-pay-field" data-pay-field="Days Worked"><label class="form-label">Daily Rate</label><input type="number" step="0.01" min="0" name="daily_rate" class="form-control" value="<?= e((string) ($old['daily_rate'] ?? '')) ?>"></div>
        <div class="col-md-3 salary-pay-field" data-pay-field="Shifts Worked"><label class="form-label">Shift Rate</label><input type="number" step="0.01" min="0" name="shift_rate" class="form-control" value="<?= e((string) ($old['shift_rate'] ?? '')) ?>"></div>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('salaryPaySourceInput');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.salary-pay-option'));
    var fields = Array.prototype.slice.call(document.querySelectorAll('.salary-pay-field'));

    function setSource(source) {
        input.value = source;
        buttons.forEach(function (button) {
            var active = button.getAttribute('data-pay-source') === source;
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('btn-light', !active);
            var note = button.querySelector('.small');
            if (note) {
                note.classList.toggle('text-white-50', active);
                note.classList.toggle('text-gray', !active);
            }
        });
        fields.forEach(function (field) {
            field.style.display = field.getAttribute('data-pay-field') === source ? '' : 'none';
        });
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            setSource(button.getAttribute('data-pay-source'));
        });
    });

    setSource(input.value || 'Fixed Salary');
});
</script>

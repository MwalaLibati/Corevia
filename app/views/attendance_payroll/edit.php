<?php
$mode = (string) ($rule['payroll_mode'] ?? 'Fixed Salary Only');
$checked = static fn(string $key): string => !empty($rule[$key]) ? 'checked' : '';
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Attendance Payroll Rules</h2>
        <p class="text-gray mb-0">Rules are versioned by effective dates, so old payroll runs remain auditable.</p>
    </div>
    <a href="<?= e(base_url('attendance-payroll/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<form method="post" action="<?= e(base_url('attendance-payroll/update/' . (string) $rule['id'])) ?>">
    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Policy Identity</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rule Set Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= e((string) $rule['name']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payroll Mode *</label>
                    <select name="payroll_mode" class="form-select">
                        <?php foreach (['Fixed Salary Only', 'Fixed Salary + Attendance Adjustments', 'Attendance-Based Payroll', 'Mixed'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= $mode === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-gray">Fixed Salary Only means current payroll remains unchanged.</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" value="<?= e((string) $rule['effective_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control" value="<?= e((string) ($rule['effective_to'] ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="attendance_impact_enabled" value="1" id="impact" <?= $checked('attendance_impact_enabled') ?>>
                        <label class="form-check-label" for="impact">Attendance affects payroll</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active" <?= $checked('is_active') ?>>
                        <label class="form-check-label" for="active">Rule set active</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Working Time Rules</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Standard Hours Per Day</label>
                    <input type="number" step="0.01" min="1" name="standard_hours_per_day" class="form-control" value="<?= e((string) $rule['standard_hours_per_day']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Standard Days Per Month</label>
                    <input type="number" step="0.01" min="1" name="standard_days_per_month" class="form-control" value="<?= e((string) $rule['standard_days_per_month']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expected Start Time</label>
                    <input type="time" name="standard_start_time" class="form-control" value="<?= e(substr((string)($rule['standard_start_time'] ?? '08:00:00'), 0, 5)) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Late Grace Minutes</label>
                    <input type="number" min="0" name="grace_minutes" class="form-control" value="<?= e((string) $rule['grace_minutes']) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">Late Coming</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="late_deduction_enabled" value="1" id="late" <?= $checked('late_deduction_enabled') ?>>
                        <label class="form-check-label" for="late">Deduct late minutes</label>
                    </div>
                    <label class="form-label">Round Late Minutes To</label>
                    <input type="number" min="1" name="late_rounding_minutes" class="form-control" value="<?= e((string) $rule['late_rounding_minutes']) ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">Short Hours</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="undertime_deduction_enabled" value="1" id="undertime" <?= $checked('undertime_deduction_enabled') ?>>
                        <label class="form-check-label" for="undertime">Deduct worked hours below standard day</label>
                    </div>
                    <label class="form-label">Round Short Minutes To</label>
                    <input type="number" min="1" name="undertime_rounding_minutes" class="form-control" value="<?= e((string) ($rule['undertime_rounding_minutes'] ?? 15)) ?>">
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">Absence</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="absence_deduction_enabled" value="1" id="absence" <?= $checked('absence_deduction_enabled') ?>>
                        <label class="form-check-label" for="absence">Deduct unapproved absences</label>
                    </div>
                    <label class="form-label">Deduction Method</label>
                    <select name="absence_deduction_method" class="form-select">
                        <?php foreach (['Daily Rate', 'Hourly Rate'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string)$rule['absence_deduction_method'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-3">Overtime</h5>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="overtime_enabled" value="1" id="ot" <?= $checked('overtime_enabled') ?>>
                        <label class="form-check-label" for="ot">Calculate overtime</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="overtime_requires_approval" value="1" id="otApproval" <?= $checked('overtime_requires_approval') ?>>
                        <label class="form-check-label" for="otApproval">Requires approval</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Minimum Minutes</label>
                            <input type="number" min="0" name="overtime_min_minutes" class="form-control" value="<?= e((string) $rule['overtime_min_minutes']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Round To</label>
                            <input type="number" min="1" name="overtime_rounding_minutes" class="form-control" value="<?= e((string) $rule['overtime_rounding_minutes']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Normal</label>
                            <input type="number" step="0.01" min="0" name="normal_overtime_multiplier" class="form-control" value="<?= e((string) $rule['normal_overtime_multiplier']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Weekend</label>
                            <input type="number" step="0.01" min="0" name="weekend_overtime_multiplier" class="form-control" value="<?= e((string) $rule['weekend_overtime_multiplier']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Holiday</label>
                            <input type="number" step="0.01" min="0" name="holiday_overtime_multiplier" class="form-control" value="<?= e((string) $rule['holiday_overtime_multiplier']) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h5 class="mb-3">Night Shift And Notes</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="night_shift_allowance_enabled" value="1" id="night" <?= $checked('night_shift_allowance_enabled') ?>>
                        <label class="form-check-label" for="night">Night shift allowance enabled</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Night Shift Allowance Amount</label>
                    <input type="number" step="0.01" min="0" name="night_shift_allowance_amount" class="form-control" value="<?= e((string) $rule['night_shift_allowance_amount']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="<?= e((string) ($rule['notes'] ?? '')) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Rules</button>
        <a href="<?= e(base_url('attendance-payroll/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<?php
$mode = (string) ($rule['payroll_mode'] ?? 'Fixed Salary Only');
$checked = static fn(string $key): string => !empty($rule[$key]) ? 'checked' : '';
$modeOptions = [
    'Fixed Salary Only' => 'Attendance is recorded, but payroll uses fixed salaries only.',
    'Fixed Salary + Attendance Adjustments' => 'Fixed salaries remain, but late coming, absences and overtime can adjust pay.',
    'Attendance-Based Payroll' => 'Hourly, daily and shift-based employees are paid from approved attendance.',
    'Mixed' => 'Only employees marked for attendance-based pay are affected.',
];
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
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h5 class="mb-1">1. Policy Identity</h5>
                    <p class="text-gray mb-0">Choose when this policy starts and whether attendance should affect payroll.</p>
                </div>
                <span class="badge bg-primary-subtle text-primary border">Company rule</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rule Set Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= e((string) $rule['name']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" value="<?= e((string) $rule['effective_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control" value="<?= e((string) ($rule['effective_to'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="attendance_impact_enabled" value="1" id="impact" <?= $checked('attendance_impact_enabled') ?>>
                        <label class="form-check-label" for="impact">Attendance affects payroll</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active" <?= $checked('is_active') ?>>
                        <label class="form-check-label" for="active">Rule set active</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Payroll Mode *</label>
                    <div class="row g-3">
                        <?php foreach ($modeOptions as $option => $description): ?>
                            <div class="col-md-3">
                                <label class="border rounded p-3 h-100 d-block <?= $mode === $option ? 'border-primary bg-primary-subtle' : 'bg-light' ?>">
                                    <input class="form-check-input me-2" type="radio" name="payroll_mode" value="<?= e($option) ?>" <?= $mode === $option ? 'checked' : '' ?>>
                                    <span class="fw-semibold d-block"><?= e($option) ?></span>
                                    <span class="small text-gray"><?= e($description) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-1">2. Working Time Rules</h5>
            <p class="text-gray mb-3">Define what the company considers a normal working day and a full payable shift.</p>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Standard Hours Per Day</label>
                    <input type="number" step="0.01" min="1" name="standard_hours_per_day" class="form-control" value="<?= e((string) $rule['standard_hours_per_day']) ?>">
                    <small class="text-gray">Used for short-hours and fixed salary attendance deductions.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Full Shift Hours</label>
                    <input type="number" step="0.01" min="1" name="full_shift_hours" class="form-control" value="<?= e((string) ($rule['full_shift_hours'] ?? $rule['standard_hours_per_day'] ?? 8)) ?>">
                    <small class="text-gray">Example: 8, 10, or 12 hours for one payable shift.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shift Count Method</label>
                    <select name="shift_count_method" class="form-select">
                        <?php foreach (['Attendance Day', 'Worked Hours / Full Shift'] as $option): ?>
                            <option value="<?= e($option) ?>" <?= (string)($rule['shift_count_method'] ?? 'Attendance Day') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-gray">Choose how shift-based basic pay is counted.</small>
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
                    <h5 class="mb-1">Overtime</h5>
                    <p class="text-gray small mb-3">Set when extra hours become overtime and how much they pay.</p>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="overtime_enabled" value="1" id="ot" <?= $checked('overtime_enabled') ?>>
                        <label class="form-check-label" for="ot">Calculate overtime</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="overtime_requires_approval" value="1" id="otApproval" <?= $checked('overtime_requires_approval') ?>>
                        <label class="form-check-label" for="otApproval">Requires approval</label>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Overtime Starts After Hours</label>
                            <input type="number" step="0.01" min="1" name="overtime_after_hours_per_day" class="form-control" value="<?= e((string) ($rule['overtime_after_hours_per_day'] ?? $rule['standard_hours_per_day'] ?? 8)) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Minimum Minutes</label>
                            <input type="number" min="0" name="overtime_min_minutes" class="form-control" value="<?= e((string) $rule['overtime_min_minutes']) ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Round To</label>
                            <input type="number" min="1" name="overtime_rounding_minutes" class="form-control" value="<?= e((string) $rule['overtime_rounding_minutes']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Normal x</label>
                            <input type="number" step="0.01" min="0" name="normal_overtime_multiplier" class="form-control" value="<?= e((string) $rule['normal_overtime_multiplier']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Weekend x</label>
                            <input type="number" step="0.01" min="0" name="weekend_overtime_multiplier" class="form-control" value="<?= e((string) $rule['weekend_overtime_multiplier']) ?>">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Holiday x</label>
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

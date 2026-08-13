<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Create Attendance Record</h2>
        <p class="text-gray mb-0">Capture daily attendance details.</p>
    </div>
    <a href="<?= e(base_url('attendance/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('attendance/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">Select employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= e((string) $employee['id']) ?>" <?= ((string) ($old['employee_id'] ?? '') === (string) $employee['id']) ? 'selected' : '' ?>>
                            <?= e((string) $employee['employee_number']) ?> - <?= e((string) $employee['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Attendance Date *</label>
                <input type="date" name="attendance_date" class="form-control" value="<?= e((string) ($old['attendance_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <?php $status = (string) ($old['status'] ?? 'Present'); ?>
                <select name="status" class="form-select">
                    <option value="Present" <?= $status === 'Present' ? 'selected' : '' ?>>Present</option>
                    <option value="Absent" <?= $status === 'Absent' ? 'selected' : '' ?>>Absent</option>
                    <option value="Late" <?= $status === 'Late' ? 'selected' : '' ?>>Late</option>
                    <option value="Leave" <?= $status === 'Leave' ? 'selected' : '' ?>>Leave</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Check In</label>
                <input type="time" name="check_in" class="form-control" value="<?= e((string) ($old['check_in'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Check Out</label>
                <input type="time" name="check_out" class="form-control" value="<?= e((string) ($old['check_out'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                    <div>
                        <strong>Hours preview:</strong>
                        <span id="attendanceHoursPreview">0.00 hrs</span>
                    </div>
                    <small class="text-gray">The money value appears on the attendance list after saving, using the employee salary basis and company payroll rules.</small>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3"><?= e((string) ($old['remarks'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Record</button>
                <a href="<?= e(base_url('attendance/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkIn = document.querySelector('input[name="check_in"]');
    var checkOut = document.querySelector('input[name="check_out"]');
    var preview = document.getElementById('attendanceHoursPreview');
    function minutes(value) {
        if (!value || value.indexOf(':') === -1) { return null; }
        var parts = value.split(':');
        return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
    }
    function update() {
        var start = minutes(checkIn ? checkIn.value : '');
        var end = minutes(checkOut ? checkOut.value : '');
        var diff = 0;
        if (start !== null && end !== null) {
            diff = end >= start ? end - start : (end + 1440) - start;
        }
        if (preview) { preview.textContent = (diff / 60).toFixed(2) + ' hrs'; }
    }
    if (checkIn) { checkIn.addEventListener('input', update); }
    if (checkOut) { checkOut.addEventListener('input', update); }
    update();
});
</script>

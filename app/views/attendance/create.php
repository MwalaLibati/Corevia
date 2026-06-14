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

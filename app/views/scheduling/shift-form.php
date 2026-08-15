<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Shift</h2>
        <p class="text-gray mb-0">Update the expected work time used by schedules and attendance comparison.</p>
    </div>
    <a href="<?= e(base_url('scheduling/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('scheduling/updateShift/' . (string)$shift['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="col-md-8"><label class="form-label">Shift Name</label><input class="form-control" name="name" value="<?= e((string)$shift['name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" value="<?= e((string)$shift['code']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" value="<?= e(substr((string)$shift['start_time'], 0, 5)) ?>" required></div>
            <div class="col-md-6"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" value="<?= e(substr((string)$shift['end_time'], 0, 5)) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Break Minutes</label><input type="number" min="0" class="form-control" name="break_minutes" value="<?= (int)$shift['break_minutes'] ?>"></div>
            <div class="col-md-3"><label class="form-label">Grace Minutes</label><input type="number" min="0" class="form-control" name="grace_minutes" value="<?= (int)$shift['grace_minutes'] ?>"></div>
            <div class="col-md-3"><label class="form-label">OT After Hours</label><input type="number" step="0.25" min="0" class="form-control" name="overtime_after_hours" value="<?= e((string)($shift['overtime_after_hours'] ?? '')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Color</label><input type="color" class="form-control form-control-color" name="color" value="<?= e((string)($shift['color'] ?? '#2563eb')) ?>"></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="shiftActive" <?= (int)$shift['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="shiftActive">Active</label></div></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Save Changes</button>
                <a href="<?= e(base_url('scheduling/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

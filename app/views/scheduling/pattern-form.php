<?php $fmtTime = static fn($time): string => $time ? substr((string) $time, 0, 5) : '-'; ?>
<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Work Pattern</h2>
        <p class="text-gray mb-0">Choose the shift expected on each day of the week.</p>
    </div>
    <a href="<?= e(base_url('scheduling/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('scheduling/updatePattern/' . (string)$pattern['id'])) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="col-md-8"><label class="form-label">Pattern Name</label><input class="form-control" name="name" value="<?= e((string)$pattern['name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" value="<?= e((string)$pattern['code']) ?>" required></div>
            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e((string)($pattern['description'] ?? '')) ?></textarea></div>
            <?php foreach (($days ?? []) as $dayNumber => $dayName): $selected = (int)($patternDays[$dayNumber]['shift_id'] ?? 0); ?>
                <div class="col-md-6">
                    <label class="form-label"><?= e((string)$dayName) ?></label>
                    <select name="day_<?= (int)$dayNumber ?>_shift_id" class="form-select">
                        <option value="0">Rest day</option>
                        <?php foreach (($activeShifts ?? []) as $shift): ?>
                            <option value="<?= (int)$shift['id'] ?>" <?= $selected === (int)$shift['id'] ? 'selected' : '' ?>>
                                <?= e((string)$shift['name']) ?> (<?= e($fmtTime($shift['start_time'])) ?>-<?= e($fmtTime($shift['end_time'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="patternActive" <?= (int)$pattern['is_active'] === 1 ? 'checked' : '' ?>><label class="form-check-label" for="patternActive">Active pattern</label></div></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary">Save Changes</button>
                <a href="<?= e(base_url('scheduling/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

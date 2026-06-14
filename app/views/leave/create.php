<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">New Leave Request</h2>
        <p class="text-gray mb-0">Submit a leave request on behalf of an employee.</p>
    </div>
    <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('leave/store')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="col-md-6">
                <label class="form-label">Employee *</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">— Select Employee —</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= (int)$emp['id'] ?>" <?= (int)($old['employee_id'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>>
                            <?= e((string)$emp['full_name']) ?> (<?= e((string)$emp['employee_number']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Leave Type *</label>
                <select name="leave_type_id" class="form-select" required>
                    <option value="">— Select Type —</option>
                    <?php foreach ($leaveTypes as $lt): ?>
                        <option value="<?= (int)$lt['id'] ?>" <?= (int)($old['leave_type_id'] ?? 0) === (int)$lt['id'] ? 'selected' : '' ?>>
                            <?= e((string)$lt['name']) ?> (<?= (int)($lt['days_per_year'] ?? 0) ?> days/yr)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Start Date *</label>
                <input type="date" name="start_date" class="form-control" value="<?= e((string)($old['start_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">End Date *</label>
                <input type="date" name="end_date" class="form-control" value="<?= e((string)($old['end_date'] ?? '')) ?>" required>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <div class="alert alert-info mb-0 w-100 py-2" id="daysPreview" style="font-size:.85rem">
                    <i class="bi bi-calendar-range me-1"></i> <span id="daysCount">—</span> day(s)
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
                <textarea name="reason" class="form-control" rows="3"><?= e((string)($old['reason'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Submit Request</button>
                <a href="<?= e(base_url('leave/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var s = document.querySelector('[name=start_date]');
    var e = document.querySelector('[name=end_date]');
    var c = document.getElementById('daysCount');
    function calc(){
        if(s.value && e.value){
            var d = (new Date(e.value) - new Date(s.value))/86400000 + 1;
            c.textContent = d > 0 ? d : '—';
        }
    }
    s.addEventListener('change', calc);
    e.addEventListener('change', calc);
    calc();
})();
</script>

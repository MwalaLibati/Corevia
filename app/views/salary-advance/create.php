<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">New Salary Advance</h2>
        <p class="text-gray mb-0">Issue a salary advance with monthly repayment schedule.</p>
    </div>
    <a href="<?= e(base_url('salary-advance/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('salary-advance/store')) ?>" class="row g-3" id="advForm">
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

            <div class="col-md-3">
                <label class="form-label">Advance Amount (ZMW) *</label>
                <input type="number" step="0.01" min="1" name="amount" id="amount" class="form-control"
                       value="<?= e((string)($old['amount'] ?? '')) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Monthly Deduction (ZMW) *</label>
                <input type="number" step="0.01" min="1" name="monthly_deduction" id="monthly" class="form-control"
                       value="<?= e((string)($old['monthly_deduction'] ?? '')) ?>" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Start Date *</label>
                <input type="date" name="start_date" class="form-control" value="<?= e((string)($old['start_date'] ?? date('Y-m-d'))) ?>" required>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="alert alert-info mb-0 w-100 py-2" style="font-size:.85rem">
                    <i class="bi bi-calendar3 me-1"></i> Est. <strong id="monthsPreview">—</strong> months to clear
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="2"><?= e((string)($old['reason'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Create Advance</button>
                <a href="<?= e(base_url('salary-advance/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    var amt = document.getElementById('amount');
    var mon = document.getElementById('monthly');
    var pre = document.getElementById('monthsPreview');
    function calc(){
        var a = parseFloat(amt.value), m = parseFloat(mon.value);
        pre.textContent = (a > 0 && m > 0) ? Math.ceil(a/m) : '—';
    }
    amt.addEventListener('input', calc);
    mon.addEventListener('input', calc);
    calc();
})();
</script>

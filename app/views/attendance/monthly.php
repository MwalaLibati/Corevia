<?php
$month = (string) ($month ?? date('Y-m'));
$rows = $rows ?? [];
$employees = $employees ?? [];
$money = static fn(float $amount): string => function_exists('format_currency')
    ? format_currency($amount)
    : number_format($amount, 2);
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h2 class="text-dark mb-1">Attendance</h2>
        <p class="text-gray mb-0">Record approved monthly hours for employees who do not use daily clock times.</p>
    </div>
    <form method="get" action="<?= e(base_url('attendance/monthly')) ?>" class="d-flex gap-2 align-items-end">
        <div>
            <label class="form-label mb-1">Month</label>
            <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
        </div>
        <button type="submit" class="btn btn-outline-primary">View</button>
    </form>
</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('attendance/index?month=' . urlencode($month))) ?>">Daily Attendance</a></li>
    <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= e(base_url('attendance/monthly?month=' . urlencode($month))) ?>">Monthly Hours</a></li>
    <li class="nav-item"><a class="nav-link" href="<?= e(base_url('attendance/import?month=' . urlencode($month))) ?>">Import Attendance</a></li>
</ul>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-1">Enter Monthly Hours</h5>
                <p class="small text-muted mb-3">Approved entries become the payroll source for the selected employee and month.</p>
                <form method="post" action="<?= e(base_url('attendance/monthlyStore')) ?>" class="row g-3" id="monthlyHoursForm">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <input type="hidden" name="attendance_month" value="<?= e($month) ?>">
                    <input type="hidden" name="employee_id" id="monthlyEmployeeId">
                    <div class="col-12">
                        <label class="form-label">Employee *</label>
                        <input type="text" id="monthlyEmployeeSearch" class="form-control" list="monthlyEmployeeOptions" placeholder="Search by name or employee ID" autocomplete="off" required>
                        <datalist id="monthlyEmployeeOptions">
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= e((string) $employee['employee_number'] . ' - ' . (string) $employee['full_name']) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Hours Worked *</label>
                        <input type="number" name="total_hours" class="form-control" min="0" max="744" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Approved Overtime</label>
                        <input type="number" name="overtime_hours" class="form-control" min="0" max="744" step="0.01" value="0">
                        <div class="form-text">Included within total hours.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Timesheet reference or approval note"></textarea>
                    </div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" name="save_action" value="Draft" class="btn btn-outline-primary">Save Draft</button>
                        <button type="submit" name="save_action" value="Approved" class="btn btn-primary">Save &amp; Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-1"><?= e(date('F Y', strtotime($month . '-01'))) ?> Monthly Entries</h5>
                        <div class="small text-muted">Payroll uses only Approved or Locked entries.</div>
                    </div>
                    <span class="badge bg-light text-dark border"><?= e((string) count($rows)) ?> employee(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Hours</th>
                            <th>Estimated Value</th>
                            <th>Payroll Source</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No monthly hours have been entered for this period.</td></tr>
                        <?php else: foreach ($rows as $row): ?>
                            <?php $estimate = $row['_estimate'] ?? []; ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) $row['employee_name']) ?></strong>
                                    <div class="small text-muted"><?= e((string) $row['employee_number']) ?></div>
                                </td>
                                <td>
                                    <strong><?= e(number_format((float) $row['total_hours'], 2)) ?> hrs</strong>
                                    <div class="small text-muted"><?= e(number_format((float) $row['overtime_hours'], 2)) ?> overtime</div>
                                </td>
                                <td>
                                    <strong><?= e($money((float) ($estimate['amount'] ?? 0))) ?></strong>
                                    <div class="small text-muted"><?= e((string) ($estimate['label'] ?? 'Information only')) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">Monthly Total</span>
                                    <?php if ((int) ($row['daily_record_count'] ?? 0) > 0): ?>
                                        <div class="small text-warning mt-1"><?= (int) $row['daily_record_count'] ?> daily record(s) excluded when approved</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= (string) $row['status'] === 'Locked' ? 'bg-dark' : ((string) $row['status'] === 'Approved' ? 'bg-success' : 'bg-warning text-dark') ?>"><?= e((string) $row['status']) ?></span></td>
                                <td class="text-end text-nowrap">
                                    <?php if ((string) $row['status'] !== 'Locked'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-monthly"
                                                data-employee-id="<?= (int) $row['employee_id'] ?>"
                                                data-employee-label="<?= e((string) $row['employee_number'] . ' - ' . (string) $row['employee_name']) ?>"
                                                data-total-hours="<?= e((string) $row['total_hours']) ?>"
                                                data-overtime-hours="<?= e((string) $row['overtime_hours']) ?>"
                                                data-notes="<?= e((string) ($row['notes'] ?? '')) ?>">Edit</button>
                                    <?php endif; ?>
                                    <?php if ((string) $row['status'] === 'Draft'): ?>
                                        <form method="post" action="<?= e(base_url('attendance/monthlyApprove/' . (string) $row['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <input type="hidden" name="month" value="<?= e($month) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Approve these monthly hours as the payroll source?');">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ((string) $row['status'] === 'Approved'): ?>
                                        <form method="post" action="<?= e(base_url('attendance/monthlyReopen/' . (string) $row['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <input type="hidden" name="month" value="<?= e($month) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reopen this entry? Daily attendance will become the payroll source until the monthly total is approved again.');">Reopen</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ((string) $row['status'] !== 'Locked'): ?>
                                        <form method="post" action="<?= e(base_url('attendance/monthlyDelete/' . (string) $row['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <input type="hidden" name="month" value="<?= e($month) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this monthly attendance entry?');"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('monthlyEmployeeSearch');
    var hidden = document.getElementById('monthlyEmployeeId');
    var form = document.getElementById('monthlyHoursForm');
    var employees = <?= json_encode(array_map(static fn(array $employee): array => [
        'id' => (string) $employee['id'],
        'label' => (string) $employee['employee_number'] . ' - ' . (string) $employee['full_name'],
    ], $employees), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    function syncEmployee() {
        var selected = employees.find(function (employee) { return employee.label === input.value.trim(); });
        hidden.value = selected ? selected.id : '';
    }
    input.addEventListener('input', syncEmployee);
    input.addEventListener('change', syncEmployee);
    form.addEventListener('submit', function (event) {
        syncEmployee();
        if (!hidden.value) {
            event.preventDefault();
            if (window.Swal) {
                Swal.fire({icon: 'warning', title: 'Select an employee', text: 'Choose an employee from the search results before saving.'});
            }
        }
    });
    document.querySelectorAll('.js-edit-monthly').forEach(function (button) {
        button.addEventListener('click', function () {
            hidden.value = button.dataset.employeeId || '';
            input.value = button.dataset.employeeLabel || '';
            form.querySelector('[name="total_hours"]').value = button.dataset.totalHours || '0';
            form.querySelector('[name="overtime_hours"]').value = button.dataset.overtimeHours || '0';
            form.querySelector('[name="notes"]').value = button.dataset.notes || '';
            input.scrollIntoView({behavior: 'smooth', block: 'center'});
            form.querySelector('[name="total_hours"]').focus();
        });
    });
})();
</script>

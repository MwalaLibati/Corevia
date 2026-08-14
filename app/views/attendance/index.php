<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Attendance & Leave</h2>
        <p class="text-gray mb-0">Track attendance, leave balances, and approvals.</p>
    </div>
    <a href="<?= e(base_url('attendance/create')) ?>" class="btn btn-primary">Add Attendance</a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php
$summary = $summary ?? ['records' => 0, 'hours' => 0, 'estimated_value' => 0, 'present' => 0, 'late' => 0, 'absent' => 0];
$money = static function (float $amount): string {
    return function_exists('format_currency') ? format_currency($amount) : 'ZMW ' . number_format($amount, 2);
};
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Records</span><div class="stat-value"><?= e((string) ($summary['records'] ?? 0)) ?></div><div class="small text-muted">For selected filter</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Hours Worked</span><div class="stat-value"><?= e(number_format((float) ($summary['hours'] ?? 0), 2)) ?></div><div class="small text-muted">From check-in/check-out</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Estimated Attendance Value</span><div class="stat-value" style="font-size:1.2rem"><?= e($money((float) ($summary['estimated_value'] ?? 0))) ?></div><div class="small text-muted">For attendance-based staff</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Attendance Mix</span><div class="stat-value" style="font-size:1.05rem"><?= e((string) ($summary['present'] ?? 0)) ?> present</div><div class="small text-muted"><?= e((string) ($summary['late'] ?? 0)) ?> late | <?= e((string) ($summary['absent'] ?? 0)) ?> absent</div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('attendance/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Employee, number, or status">
            </div>
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-select">
                    <option value="0">All employees</option>
                    <?php foreach (($employees ?? []) as $employee): ?>
                        <option value="<?= e((string) $employee['id']) ?>" <?= (int)($filters['employee_id'] ?? 0) === (int)$employee['id'] ? 'selected' : '' ?>>
                            <?= e((string) $employee['employee_number']) ?> - <?= e((string) $employee['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Month</label>
                <input type="month" class="form-control" name="month" value="<?= e((string) ($filters['month'] ?? date('Y-m'))) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="<?= e(base_url('attendance/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Status</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Hours</th>
                    <th>Estimated Value</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr><td colspan="8" class="text-center text-gray">No attendance records found.</td></tr>
            <?php else: ?>
                <?php foreach ($records as $record): ?>
                    <?php $calc = $record['_attendance_calc'] ?? ['hours' => 0, 'amount' => 0, 'label' => 'Time only']; ?>
                    <tr>
                        <td><?= e((string) ($record['attendance_date'] ?? '')) ?></td>
                        <td><?= e((string) ($record['employee_name'] ?? '')) ?></td>
                        <td><?= e((string) ($record['status'] ?? '')) ?></td>
                        <td><?= e((string) ($record['check_in'] ?? '-')) ?></td>
                        <td><?= e((string) ($record['check_out'] ?? '-')) ?></td>
                        <td><strong><?= e(number_format((float) ($calc['hours'] ?? 0), 2)) ?></strong> hrs</td>
                        <td>
                            <div class="fw-semibold"><?= e($money((float) ($calc['amount'] ?? 0))) ?></div>
                            <div class="small text-gray"><?= e((string) ($calc['label'] ?? 'Time only')) ?></div>
                        </td>
                        <td class="text-end">
                            <a href="<?= e(base_url('attendance/edit/' . (string) $record['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('attendance/delete/' . (string) $record['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this record?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

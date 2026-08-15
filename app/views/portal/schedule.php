<?php
$rows = $rows ?? [];
$month = (string)($month ?? date('Y-m'));
$fmtTime = static fn($time): string => $time ? substr((string)$time, 0, 5) : '-';
$scheduled = array_values(array_filter($rows, static fn($row): bool => !empty($row['schedule']['shift_id'])));
$restDays = count($rows) - count($scheduled);
$expectedHours = array_sum(array_map(static fn($row): float => (float)($row['schedule']['expected_hours'] ?? 0), $scheduled));
$activeShifts = $activeShifts ?? [];
$requests = $requests ?? [];
$rowsByDate = [];
foreach ($rows as $row) {
    $rowsByDate[(string)$row['date']] = $row;
}
$monthStart = $month . '-01';
$daysInMonth = (int) date('t', strtotime($monthStart));
$firstDow = (int) date('N', strtotime($monthStart));
$calendarCells = [];
for ($blank = 1; $blank < $firstDow; $blank++) {
    $calendarCells[] = null;
}
for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = $month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT);
    $calendarCells[] = $rowsByDate[$date] ?? ['date' => $date, 'schedule' => null];
}
while (count($calendarCells) % 7 !== 0) {
    $calendarCells[] = null;
}
?>

<style>
.portal-schedule-calendar{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:1px;background:#dbe3ef;border:1px solid #dbe3ef;border-radius:12px;overflow:hidden}
.portal-schedule-day-head{background:#f8fafc;padding:10px;text-align:center;font-weight:700;color:#52627a;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}
.portal-schedule-cell{background:#fff;min-height:118px;padding:10px;display:flex;flex-direction:column;gap:8px}
.portal-schedule-cell.is-empty{background:#f8fafc}
.portal-schedule-cell.is-rest{background:#f9fafb}
.portal-schedule-date{font-weight:800;color:#0f172a}
.portal-schedule-shift{border-left:4px solid #2563eb;background:#eff6ff;border-radius:8px;padding:8px}
.portal-schedule-shift strong{display:block;font-size:.86rem;color:#0f172a}
.portal-schedule-shift span{display:block;font-size:.76rem;color:#52627a}
.portal-schedule-rest{border-left:4px solid #94a3b8;background:#f1f5f9;border-radius:8px;padding:8px;font-size:.8rem;color:#475569;font-weight:700}
@media(max-width:767.98px){
    .portal-schedule-calendar{display:block;border-radius:10px;background:transparent;border:0}
    .portal-schedule-day-head{display:none}
    .portal-schedule-cell{min-height:auto;border:1px solid #dbe3ef;border-radius:10px;margin-bottom:8px}
    .portal-schedule-cell.is-empty{display:none}
}
</style>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="text-dark mb-0">My Schedule</h2>
        <p class="text-muted mb-0 mt-1">View your expected shifts and rest days for the selected month.</p>
    </div>
    <form method="get" action="<?= e(base_url('portal/schedule')) ?>" class="d-flex gap-2">
        <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Scheduled Days</span><div class="stat-value"><?= count($scheduled) ?></div><div class="small text-muted"><?= e(date('F Y', strtotime($month . '-01'))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Expected Hours</span><div class="stat-value"><?= e(number_format((float)$expectedHours, 2)) ?></div><div class="small text-muted">From assigned shifts</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Rest Days</span><div class="stat-value"><?= $restDays ?></div><div class="small text-muted">Days without assigned shift</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Schedule Status</span><div class="stat-value" style="font-size:1.05rem"><?= count($scheduled) > 0 ? 'Assigned' : 'Not set' ?></div><div class="small text-muted">Contact HR for changes</div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Request Schedule Change</h6>
                <div class="small text-muted mb-3">Send HR a request for a shift change, rest day, or correction.</div>
                <form method="post" action="<?= e(base_url('portal/requestScheduleChange')) ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? '')) ?>">
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="date" name="requested_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Requested Shift</label>
                        <select name="requested_shift_id" class="form-select">
                            <option value="0">Rest day / no shift</option>
                            <?php foreach ($activeShifts as $shift): ?>
                                <option value="<?= (int)$shift['id'] ?>"><?= e((string)$shift['name']) ?> (<?= e($fmtTime($shift['start_time'])) ?>-<?= e($fmtTime($shift['end_time'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Explain why this change is needed" required></textarea>
                    </div>
                    <div class="col-12"><button class="btn btn-primary w-100">Submit Request</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h6 class="fw-bold mb-1"><i class="bi bi-inbox me-2 text-primary"></i>Recent Requests</h6>
                        <div class="small text-muted">Track requests sent to HR.</div>
                    </div>
                    <span class="badge bg-light text-dark border"><?= count($requests) ?> request(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>Date</th><th>Requested</th><th>Status</th><th>Reviewed</th></tr></thead>
                        <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No schedule change requests yet.</td></tr>
                        <?php else: foreach ($requests as $request): ?>
                            <tr>
                                <td><?= e((string)$request['requested_date']) ?><div class="small text-muted"><?= e((string)($request['reason'] ?? '')) ?></div></td>
                                <td><?= e((string)($request['requested_shift_name'] ?? 'Rest day / no shift')) ?></td>
                                <td><span class="badge bg-<?= (string)$request['status'] === 'Pending' ? 'warning' : ((string)$request['status'] === 'Approved' ? 'success' : 'secondary') ?>"><?= e((string)$request['status']) ?></span></td>
                                <td><?= e((string)($request['reviewed_at'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-calendar-week me-2 text-primary"></i>Calendar View</h6>
                <div class="small text-muted">Your expected shifts shown by day.</div>
            </div>
            <span class="badge bg-light text-dark border"><?= e(date('F Y', strtotime($monthStart))) ?></span>
        </div>

        <div class="portal-schedule-calendar">
            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dayName): ?>
                <div class="portal-schedule-day-head"><?= e($dayName) ?></div>
            <?php endforeach; ?>
            <?php foreach ($calendarCells as $cell): ?>
                <?php if ($cell === null): ?>
                    <div class="portal-schedule-cell is-empty"></div>
                    <?php continue; ?>
                <?php endif; ?>
                <?php $schedule = $cell['schedule'] ?? null; $hasShift = !empty($schedule['shift_id']); ?>
                <div class="portal-schedule-cell <?= $hasShift ? '' : 'is-rest' ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="portal-schedule-date"><?= e(date('j', strtotime((string)$cell['date']))) ?></span>
                        <span class="small text-muted"><?= e(date('D', strtotime((string)$cell['date']))) ?></span>
                    </div>
                    <?php if ($hasShift): ?>
                        <div class="portal-schedule-shift">
                            <strong><?= e((string)$schedule['shift_name']) ?></strong>
                            <span><?= e($fmtTime($schedule['start_time'])) ?> - <?= e($fmtTime($schedule['end_time'])) ?></span>
                            <span><?= e(number_format((float)($schedule['expected_hours'] ?? 0), 2)) ?> expected hrs</span>
                        </div>
                    <?php else: ?>
                        <div class="portal-schedule-rest"><?= e((string)($schedule['pattern_name'] ?? 'Rest day')) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-calendar3-range me-2 text-primary"></i>Schedule Details</h6>
                <div class="small text-muted">This is the expected schedule used when attendance is compared for payroll.</div>
            </div>
            <span class="badge bg-light text-dark border"><?= e(date('F Y', strtotime($month . '-01'))) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Pattern</th>
                        <th>Shift</th>
                        <th>Expected Hours</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No schedule rows found.</td></tr>
                <?php else: foreach ($rows as $row): $schedule = $row['schedule'] ?? null; ?>
                    <tr>
                        <td><?= e((string)$row['date']) ?></td>
                        <td><?= e(date('l', strtotime((string)$row['date']))) ?></td>
                        <td><?= e((string)($schedule['pattern_name'] ?? 'No assignment')) ?></td>
                        <td>
                            <?php if (!empty($schedule['shift_id'])): ?>
                                <strong><?= e((string)$schedule['shift_name']) ?></strong>
                                <div class="small text-muted"><?= e($fmtTime($schedule['start_time'])) ?> - <?= e($fmtTime($schedule['end_time'])) ?></div>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border">Rest day</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(number_format((float)($schedule['expected_hours'] ?? 0), 2)) ?> hrs</td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

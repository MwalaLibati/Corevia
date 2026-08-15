<?php
$rows = $rows ?? [];
$month = (string)($month ?? date('Y-m'));
$fmtTime = static fn($time): string => $time ? substr((string)$time, 0, 5) : '-';
$scheduled = array_values(array_filter($rows, static fn($row): bool => !empty($row['schedule']['shift_id'])));
$restDays = count($rows) - count($scheduled);
$expectedHours = array_sum(array_map(static fn($row): float => (float)($row['schedule']['expected_hours'] ?? 0), $scheduled));
?>

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

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Scheduled Days</span><div class="stat-value"><?= count($scheduled) ?></div><div class="small text-muted"><?= e(date('F Y', strtotime($month . '-01'))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Expected Hours</span><div class="stat-value"><?= e(number_format((float)$expectedHours, 2)) ?></div><div class="small text-muted">From assigned shifts</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Rest Days</span><div class="stat-value"><?= $restDays ?></div><div class="small text-muted">Days without assigned shift</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Schedule Status</span><div class="stat-value" style="font-size:1.05rem"><?= count($scheduled) > 0 ? 'Assigned' : 'Not set' ?></div><div class="small text-muted">Contact HR for changes</div></div></div>
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

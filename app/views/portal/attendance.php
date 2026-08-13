<?php
$summary = $summary ?? ['records' => 0, 'hours' => 0, 'estimated_value' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];
$records = $records ?? [];
$month = (string) ($month ?? date('Y-m'));
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="text-dark mb-0">My Attendance</h2>
        <p class="text-muted mb-0 mt-1">Review your recorded hours and attendance-based payroll value.</p>
    </div>
    <form method="get" action="<?= e(base_url('portal/attendance')) ?>" class="d-flex gap-2">
        <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card h-100">
            <span class="stat-label">Records</span>
            <div class="stat-value"><?= e((string) ($summary['records'] ?? 0)) ?></div>
            <div class="small text-muted">Attendance entries this month</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card h-100">
            <span class="stat-label">Hours Worked</span>
            <div class="stat-value"><?= e(number_format((float) ($summary['hours'] ?? 0), 2)) ?></div>
            <div class="small text-muted">Based on check-in and check-out</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card h-100">
            <span class="stat-label">Estimated Value</span>
            <div class="stat-value" style="font-size:1.2rem"><?= e(format_currency((float) ($summary['estimated_value'] ?? 0))) ?></div>
            <div class="small text-muted">For attendance-based pay only</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="ent-stat-card h-100">
            <span class="stat-label">Attendance Mix</span>
            <div class="stat-value" style="font-size:1.05rem"><?= e((string) ($summary['present'] ?? 0)) ?> present</div>
            <div class="small text-muted"><?= e((string) ($summary['late'] ?? 0)) ?> late | <?= e((string) ($summary['absent'] ?? 0)) ?> absent | <?= e((string) ($summary['leave'] ?? 0)) ?> leave</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Attendance Details</h6>
                <div class="small text-muted">Values shown here are estimates until payroll is generated and approved.</div>
            </div>
            <span class="badge bg-light text-dark border"><?= e(date('F Y', strtotime($month . '-01'))) ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>Estimated Value</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No attendance records found for this month.</td></tr>
                <?php else: foreach ($records as $record): ?>
                    <?php $calc = $record['_attendance_calc'] ?? ['hours' => 0, 'amount' => 0, 'label' => 'Time record']; ?>
                    <tr>
                        <td><?= e((string) ($record['attendance_date'] ?? '')) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e((string) ($record['status'] ?? '')) ?></span></td>
                        <td><?= e((string) ($record['check_in'] ?? '-')) ?></td>
                        <td><?= e((string) ($record['check_out'] ?? '-')) ?></td>
                        <td><strong><?= e(number_format((float) ($calc['hours'] ?? 0), 2)) ?></strong> hrs</td>
                        <td>
                            <div class="fw-semibold"><?= e(format_currency((float) ($calc['amount'] ?? 0))) ?></div>
                            <div class="small text-muted"><?= e((string) ($calc['label'] ?? 'Time record')) ?></div>
                        </td>
                        <td class="text-muted"><?= e((string) ($record['remarks'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Payroll Preview</h2>
        <p class="text-gray mb-0">Review calculated payroll before generating approval items.</p>
    </div>
    <a href="<?= e(base_url('payroll/edit/' . (string) $run['id'])) ?>" class="btn btn-outline-secondary">Back to Run</a>
</div>

<?php
    $items = $preview['items'] ?? [];
    $employerTotal = (float) ($preview['employer_contributions'] ?? 0);
    $attendancePreview = $attendancePreview ?? ($preview['attendance'] ?? []);
    $attendanceEnabled = !empty($attendancePreview['enabled']);
    $attendanceTotals = $attendancePreview['totals'] ?? ['earnings' => 0, 'deductions' => 0];
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Employees</span><div class="stat-value"><?= e((string) ($preview['employees'] ?? 0)) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Gross</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float) ($preview['gross'] ?? 0))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Deductions</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float) ($preview['deductions'] ?? 0))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Employer Contributions</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency($employerTotal)) ?></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h5 class="mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Attendance Payroll Impact</h5>
            <p class="text-gray mb-0">
                <?= $attendanceEnabled
                    ? 'Attendance rules are enabled for this period and have been included in this preview.'
                    : 'Attendance payroll impact is off for this company/period. Fixed salary payroll remains unchanged.' ?>
            </p>
            <div class="text-gray small mt-2">
                Hourly, daily and shift-based staff can have Basic Pay calculated from approved attendance. Fixed monthly staff continue using their agreed monthly salary.
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-<?= $attendanceEnabled ? 'success' : 'secondary' ?> px-3 py-2"><?= $attendanceEnabled ? 'Enabled' : 'Off' ?></span>
            <span class="badge bg-light text-dark border px-3 py-2">Earnings: <?= e(format_currency((float)($attendanceTotals['earnings'] ?? 0))) ?></span>
            <span class="badge bg-light text-dark border px-3 py-2">Deductions: <?= e(format_currency((float)($attendanceTotals['deductions'] ?? 0))) ?></span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="mb-1">Preview Items</h5>
                <p class="text-gray mb-0">This preview does not change balances, bonuses, approvals, or payslip records.</p>
            </div>
            <?php if ((int)($run['is_locked'] ?? 0) !== 1 && (string)($run['status'] ?? '') === 'Draft'): ?>
                <form method="post" action="<?= e(base_url('payroll/process/' . (string) $run['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Generate payroll items from this preview?');">Generate Items</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employment Coverage</th>
                        <th>Gross</th>
                        <th>Employee Deductions</th>
                        <th>Net</th>
                        <th>Breakdown</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center text-gray">No employees employed during this period with an effective salary assignment were found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string) ($item['employee_name'] ?? '')) ?></div>
                                    <div class="text-gray small"><?= e((string) ($item['employee_number'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e((string)($item['eligible_days'] ?? 0)) ?> / <?= e((string)($item['period_days'] ?? 0)) ?> days</div>
                                    <div class="text-gray small"><?= e((string)($item['proration_mode'] ?? 'Full Month')) ?> · <?= e(number_format((float)($item['proration_factor'] ?? 1) * 100, 2)) ?>%</div>
                                </td>
                                <td><?= e(format_currency((float) ($item['gross_pay'] ?? 0))) ?></td>
                                <td><?= e(format_currency((float) ($item['total_deductions'] ?? 0))) ?></td>
                                <td><?= e(format_currency((float) ($item['net_pay'] ?? 0))) ?></td>
                                <td>
                                    <?php if (!empty($item['basic_pay_explanation'])): ?>
                                        <div class="small mb-2">
                                            <span class="badge bg-primary-subtle text-primary border">basic pay</span>
                                            <?= e((string) $item['basic_pay_explanation']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php foreach (($item['pay_warnings'] ?? []) as $warning): ?>
                                        <div class="small text-warning mb-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i><?= e((string) $warning) ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php foreach (($item['earning_lines'] ?? []) as $line): ?>
                                        <?php if (($line['category'] ?? '') === 'attendance'): ?>
                                            <div class="small">
                                                <span class="badge bg-success-subtle text-success border">attendance earning</span>
                                                <?= e((string) ($line['name'] ?? '')) ?>:
                                                <?= e(format_currency((float) ($line['amount'] ?? 0))) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php foreach (($item['deduction_lines'] ?? []) as $line): ?>
                                        <div class="small">
                                            <span class="badge bg-light text-dark border"><?= e((string) ($line['category'] ?? '')) ?></span>
                                            <?= e((string) ($line['name'] ?? '')) ?>:
                                            <?= e(format_currency((float) ($line['amount'] ?? 0))) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

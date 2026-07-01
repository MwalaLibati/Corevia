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
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Employees</span><div class="stat-value"><?= e((string) ($preview['employees'] ?? 0)) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Gross</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float) ($preview['gross'] ?? 0))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Deductions</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency((float) ($preview['deductions'] ?? 0))) ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Employer Contributions</span><div class="stat-value" style="font-size:1.25rem"><?= e(format_currency($employerTotal)) ?></div></div></div>
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

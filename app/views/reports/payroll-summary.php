<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Payroll Summary Report</h2>
        <p class="text-gray mb-0">Summary of payroll totals by period with employee counts.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('report/payrollSummary?export=csv')) ?>" class="btn btn-outline-primary">Export CSV</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Pay Period</th>
                    <th>Run Date</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($runs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray">No payroll runs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($runs as $run): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($run['pay_period'] ?? '')) ?></td>
                            <td><?= e((string) ($run['run_date'] ?? '')) ?></td>
                            <td><?= e((string) ((int) ($run['employee_count'] ?? 0))) ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= e((string) ($run['status'] ?? '')) ?></span>
                            </td>
                            <td><?= e(format_currency((float) ($run['total_gross'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($run['total_deductions'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($run['total_net'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

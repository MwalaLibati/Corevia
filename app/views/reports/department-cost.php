<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Department Cost Report</h2>
        <p class="text-gray mb-0">Department-wise payroll distribution and totals.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('report/departmentCost?export=csv')) ?>" class="btn btn-outline-primary">Export CSV</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Items</th>
                    <th>Gross</th>
                    <th>Deductions</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray">No payroll data found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($department['department_name'] ?? '')) ?></td>
                            <td><?= e((string) ((int) ($department['item_count'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($department['total_gross'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($department['total_deductions'] ?? 0))) ?></td>
                            <td><?= e(format_currency((float) ($department['total_net'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

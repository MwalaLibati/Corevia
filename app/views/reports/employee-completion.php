<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Employee Completion Report</h2>
        <p class="text-gray mb-0">Profile and required-document readiness across employees.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('report/employeeCompletion?export=csv')) ?>" class="btn btn-outline-primary">Export CSV</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Employee #</th>
                    <th>Department</th>
                    <th style="width:180px">Completion</th>
                    <th>Missing Items</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-gray">No employees found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php $employee = $row['employee'] ?? []; $percent = (int) ($row['percent'] ?? 0); ?>
                        <tr>
                            <td class="fw-semibold"><?= e((string) ($employee['full_name'] ?? '')) ?></td>
                            <td><?= e((string) ($employee['employee_number'] ?? '')) ?></td>
                            <td><?= e((string) ($employee['department_name'] ?? '-')) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px">
                                        <div class="progress-bar <?= $percent >= 90 ? 'bg-success' : ($percent >= 60 ? 'bg-warning' : 'bg-danger') ?>" style="width:<?= $percent ?>%"></div>
                                    </div>
                                    <span class="fw-semibold"><?= $percent ?>%</span>
                                </div>
                            </td>
                            <td style="font-size:.82rem">
                                <?= empty($row['missing']) ? '<span class="text-success">Complete</span>' : e(implode(', ', (array) $row['missing'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

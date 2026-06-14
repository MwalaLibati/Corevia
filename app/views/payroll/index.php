<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Payroll Processing</h2>
        <p class="text-gray mb-0">Run payroll by period and manage approvals.</p>
    </div>
    <a href="<?= e(base_url('payroll/create')) ?>" class="btn btn-primary">Create Payroll Run</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('payroll/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="<?= e((string) ($search ?? '')) ?>" placeholder="Pay period or status">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a href="<?= e(base_url('payroll/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Pay Period</th>
                    <th>Run Date</th>
                    <th>Status</th>
                    <th>Total Gross</th>
                    <th>Total Deductions</th>
                    <th>Total Net</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($runs)): ?>
                <tr><td colspan="7" class="text-center text-gray">No payroll runs found.</td></tr>
            <?php else: ?>
                <?php foreach ($runs as $run): ?>
                    <tr>
                        <td><?= e((string) ($run['pay_period'] ?? '')) ?></td>
                        <td><?= e((string) ($run['run_date'] ?? '')) ?></td>
                        <td><?= e((string) ($run['status'] ?? '')) ?></td>
                        <td><?= e(format_currency((float) ($run['total_gross'] ?? 0))) ?></td>
                        <td><?= e(format_currency((float) ($run['total_deductions'] ?? 0))) ?></td>
                        <td><?= e(format_currency((float) ($run['total_net'] ?? 0))) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('payroll/edit/' . (string) $run['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="post" action="<?= e(base_url('payroll/delete/' . (string) $run['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this payroll run?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

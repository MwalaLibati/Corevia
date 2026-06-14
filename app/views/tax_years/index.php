<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Tax Year Configuration</h2>
        <p class="text-gray mb-0">Define the tax periods used for payroll runs and statutory reporting.</p>
    </div>
    <a href="<?= e(base_url('deduction/index')) ?>" class="btn btn-outline-secondary">Back to Deductions</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Add Tax Year</h5>
                <form method="post" action="<?= e(base_url('tax-year/store')) ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <div class="col-12">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="Tax Year <?= e(date('Y')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Starts On *</label>
                        <input type="date" name="starts_on" class="form-control" value="<?= e(date('Y-01-01')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ends On *</label>
                        <input type="date" name="ends_on" class="form-control" value="<?= e(date('Y-12-31')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Tax Year</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($years)): ?>
                            <tr><td colspan="5" class="text-center text-gray">No tax years configured.</td></tr>
                        <?php else: ?>
                            <?php foreach ($years as $year): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $year['name']) ?></td>
                                    <td><?= e((string) $year['starts_on']) ?> to <?= e((string) $year['ends_on']) ?></td>
                                    <td><?= (int)($year['is_active'] ?? 0) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                                    <td><?= e((string) ($year['notes'] ?? '-')) ?></td>
                                    <td class="text-end">
                                        <form method="post" action="<?= e(base_url('tax-year/toggle/' . (string) $year['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <?= (int)($year['is_active'] ?? 0) === 1 ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Contract Expiry Report</h2>
        <p class="text-gray mb-0">Contracts expiring within the selected date range.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
        <a href="<?= e(base_url('report/contractExpiry') . '?date_from=' . e((string)$dateFrom) . '&date_to=' . e((string)$dateTo) . '&export=csv') ?>" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('report/contractExpiry')) ?>" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Expiry From</label>
                <input type="date" name="date_from" class="form-control" value="<?= e((string)$dateFrom) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Expiry To</label>
                <input type="date" name="date_to" class="form-control" value="<?= e((string)$dateTo) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <?php if (empty($contracts)): ?>
            <p class="text-muted text-center py-4">No contracts expiring between <?= e((string)$dateFrom) ?> and <?= e((string)$dateTo) ?>.</p>
        <?php else: ?>
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Emp #</th>
                        <th>Designation</th>
                        <th>Contract No.</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th class="text-center">Days Left</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contracts as $c): ?>
                    <?php
                        $daysLeft = (int) $c['days_remaining'];
                        $badgeClass = $daysLeft < 0 ? 'danger' : ($daysLeft <= 7 ? 'danger' : ($daysLeft <= 30 ? 'warning' : 'success'));
                    ?>
                    <tr>
                        <td><?= e((string) $c['employee_name']) ?></td>
                        <td><code><?= e((string) $c['employee_number']) ?></code></td>
                        <td><?= e((string) ($c['designation'] ?? '—')) ?></td>
                        <td><code><?= e((string) ($c['contract_number'] ?? '—')) ?></code></td>
                        <td><?= e((string) ($c['start_date'] ?? '—')) ?></td>
                        <td><?= e((string) ($c['end_date'] ?? '—')) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $badgeClass ?>">
                                <?= $daysLeft < 0 ? 'Expired ' . abs($daysLeft) . 'd ago' : $daysLeft . ' days' ?>
                            </span>
                        </td>
                        <td><?= e((string) ($c['status'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

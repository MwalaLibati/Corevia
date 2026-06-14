<div class="portal-page-header">
    <h2><i class="bi bi-file-earmark-text me-2"></i>My Contracts</h2>
    <p>Your employment contract history.</p>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php if (empty($contracts)): ?>
    <div class="portal-card text-center py-5 text-muted">
        <i class="bi bi-file-earmark-x fs-1 d-block mb-3"></i>
        No contracts found on your record.
    </div>
<?php else: ?>
    <?php foreach ($contracts as $c):
        $statusColors = ['Active' => 'success', 'Expired' => 'danger', 'Terminated' => 'secondary'];
        $statusClass  = $statusColors[$c['status']] ?? 'secondary';
        $daysLeft     = $c['end_date'] ? (int) (strtotime($c['end_date']) - strtotime(date('Y-m-d'))) / 86400 : null;
    ?>
    <div class="portal-card mb-3">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <div class="fw-bold" style="font-size:1rem;color:var(--portal-text)"><?= e((string)($c['contract_type'] ?? 'Employment Contract')) ?></div>
                <div style="font-size:.8rem;color:#6b7280"><code><?= e((string)($c['contract_number'] ?? '')) ?></code></div>
            </div>
            <span class="badge bg-<?= $statusClass ?> px-3 py-2"><?= e((string)($c['status'] ?? '')) ?></span>
        </div>
        <div class="row g-3" style="font-size:.85rem">
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1">Start Date</div>
                <div class="fw-semibold"><?= e((string)($c['start_date'] ?? '—')) ?></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1">End Date</div>
                <div class="fw-semibold"><?= e($c['end_date'] ? (string)$c['end_date'] : 'Permanent') ?></div>
            </div>
            <?php if ($daysLeft !== null && $c['status'] === 'Active'): ?>
            <div class="col-6 col-md-3">
                <div class="text-muted mb-1">Days Remaining</div>
                <div class="fw-semibold <?= $daysLeft <= 30 ? 'text-danger' : 'text-success' ?>">
                    <?= $daysLeft > 0 ? $daysLeft . ' days' : 'Expired' ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($c['notes'])): ?>
            <div class="col-12">
                <div class="text-muted mb-1">Notes</div>
                <div><?= e((string)$c['notes']) ?></div>
            </div>
            <?php endif; ?>
            <div class="col-12 mt-2 d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-primary" href="<?= e(base_url('portal/contractView/' . (string) $c['id'])) ?>">
                    <i class="bi bi-download me-1"></i> View / Download Contract
                </a>
                <?php $pendingRequest = ($pendingRequests ?? [])[(int) $c['id']] ?? null; ?>
                <?php if ($pendingRequest): ?>
                    <span class="badge bg-warning text-dark align-self-center px-3 py-2">
                        <i class="bi bi-hourglass-split me-1"></i> Renewal requested
                    </span>
                <?php elseif (in_array((string)($c['status'] ?? ''), ['Active', 'Expired'], true)): ?>
                    <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#renewContract<?= e((string)$c['id']) ?>">
                        <i class="bi bi-arrow-repeat me-1"></i> Request Renewal
                    </button>
                <?php endif; ?>
            </div>
            <?php if (!$pendingRequest && in_array((string)($c['status'] ?? ''), ['Active', 'Expired'], true)): ?>
                <div class="col-12">
                    <div class="collapse mt-2" id="renewContract<?= e((string)$c['id']) ?>">
                        <form method="post" action="<?= e(base_url('portal/contractRenewalRequest/' . (string) $c['id'])) ?>" class="p-3 rounded border bg-light">
                            <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? '')) ?>">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Preferred New End Date</label>
                                    <input type="date" name="requested_end_date" class="form-control">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Reason / Notes</label>
                                    <input type="text" name="reason" class="form-control" placeholder="Optional message to HR">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-send me-1"></i> Submit Renewal Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="text-muted mt-3" style="font-size:.76rem">
    <i class="bi bi-lock me-1"></i> Renewal requests are sent to HR for review before a new contract is prepared.
</div>

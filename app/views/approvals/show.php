<?php
$approvalItem = $approvalItem ?? [];
$detailRows = $detailRows ?? [];
$detailTables = $detailTables ?? [];
$workflowEvents = $workflowEvents ?? [];
$approveRoute = (string) ($approveRoute ?? '');
$rejectRoute = (string) ($rejectRoute ?? '');
$moduleRoute = (string) ($moduleRoute ?? '');
$canReject = (bool) ($canReject ?? false);
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-1">Approval Review</h2>
        <p class="text-gray mb-0"><?= e((string) ($approvalItem['title'] ?? 'Review approval item')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($moduleRoute !== ''): ?>
            <a href="<?= e(base_url($moduleRoute)) ?>" class="btn btn-outline-secondary btn-sm">Open Module</a>
        <?php endif; ?>
        <a href="<?= e(base_url('approval-inbox/index')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Inbox
        </a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <span style="width:46px;height:46px;border-radius:12px;background:#eff6ff;color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0">
                        <i class="bi <?= e((string) ($approvalItem['icon'] ?? 'bi-list-check')) ?>"></i>
                    </span>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h5 class="mb-0"><?= e((string) ($approvalItem['type'] ?? 'Approval')) ?></h5>
                            <span class="badge text-bg-warning"><?= e((string) ($approvalItem['stage'] ?? 'Pending')) ?></span>
                        </div>
                        <div class="text-muted"><?= e((string) ($approvalItem['subject'] ?? '')) ?></div>
                    </div>
                </div>

                <div class="row g-3">
                    <?php foreach ($detailRows as $row): ?>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <div class="text-muted small text-uppercase fw-semibold mb-1" style="letter-spacing:.04em"><?= e((string) ($row[0] ?? 'Detail')) ?></div>
                                <div class="fw-semibold"><?= nl2br(e((string) ($row[1] ?? '-'))) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php foreach ($detailTables as $table): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><?= e((string) ($table['title'] ?? 'Details')) ?></h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 no-pagination">
                            <thead>
                                <tr>
                                    <?php foreach (($table['headers'] ?? []) as $header): ?>
                                        <th><?= e((string) $header) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($table['rows'])): ?>
                                    <tr><td colspan="<?= max(1, count($table['headers'] ?? [])) ?>" class="text-center text-muted py-3">No records found.</td></tr>
                                <?php else: foreach (($table['rows'] ?? []) as $tableRow): ?>
                                    <tr>
                                        <?php foreach ($tableRow as $cell): ?>
                                            <?php $cellValue = (string) $cell; ?>
                                            <td><?= str_starts_with($cellValue, '<a ') ? $cellValue : e($cellValue) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Workflow History</h6>
                <?php if (empty($workflowEvents)): ?>
                    <p class="text-muted mb-0">No workflow history has been recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 no-pagination">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>By</th>
                                    <th>At</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workflowEvents as $event): ?>
                                    <tr>
                                        <td><?= e((string) ($event['action'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['from_status'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['to_status'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['actor_name'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['created_at'] ?? '-')) ?></td>
                                        <td><?= e((string) ($event['notes'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm position-sticky" style="top:90px">
            <div class="card-body">
                <h5 class="mb-2">Approval Decision</h5>
                <p class="text-muted">Review the details, then approve the current workflow stage or reject where supported.</p>

                <div class="border rounded-3 p-3 mb-3 bg-light">
                    <div class="text-muted small">Required Role</div>
                    <div class="fw-bold"><?= e((string) ($approvalItem['required_role'] ?? '-')) ?></div>
                </div>

                <form method="post" action="<?= e(base_url($approveRoute)) ?>" class="mb-3">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Approve this workflow item?');">
                        <i class="bi bi-check2-circle me-1"></i><?= e((string) ($approvalItem['action_label'] ?? 'Approve')) ?>
                    </button>
                </form>

                <?php if ($canReject): ?>
                    <form method="post" action="<?= e(base_url($rejectRoute)) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <label class="form-label">Rejection Reason</label>
                        <textarea name="reason" class="form-control mb-2" rows="3" placeholder="Capture the reason for rejection"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Reject this workflow item?');">
                            <i class="bi bi-x-circle me-1"></i>Reject
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        This workflow stage does not support rejection from this screen.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

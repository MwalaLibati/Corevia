<?php
$approvalInboxItems = $approvalInboxItems ?? [];
$approvalInboxSummary = $approvalInboxSummary ?? ['total' => 0, 'by_type' => []];
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-1">My Approvals</h2>
        <p class="text-gray mb-0">Work items assigned to your role in the approval workflow.</p>
    </div>
    <a href="<?= e(base_url('dashboard/index')) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:#1d4ed8">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="stat-label">Pending For Me</span>
                <span class="stat-icon" style="background:#dbeafe;color:#1d4ed8"><i class="bi bi-inbox"></i></span>
            </div>
            <div class="stat-value"><?= number_format((int) ($approvalInboxSummary['total'] ?? 0)) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Based on your current role</div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Approval Types</h6>
                <?php if (empty($approvalInboxSummary['by_type'])): ?>
                    <p class="text-muted mb-0">No approval types waiting.</p>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($approvalInboxSummary['by_type'] as $type => $count): ?>
                            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                                <?= e((string) $type) ?>: <?= number_format((int) $count) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="ent-section-header mb-3">
            <h6 class="mb-0 fw-bold">Approval Queue</h6>
            <span class="text-muted small"><?= number_format(count($approvalInboxItems)) ?> item(s)</span>
        </div>

        <?php if (empty($approvalInboxItems)): ?>
            <div class="text-center py-5">
                <div class="mx-auto mb-3" style="width:52px;height:52px;border-radius:14px;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.35rem">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h5 class="mb-1">No approvals waiting for you</h5>
                <p class="text-muted mb-0">When a workflow reaches your role, it will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Stage</th>
                            <th>Details</th>
                            <th>Submitted</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($approvalInboxItems as $approvalItem): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:34px;height:34px;border-radius:10px;background:#eff6ff;color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center">
                                        <i class="bi <?= e((string) ($approvalItem['icon'] ?? 'bi-list-check')) ?>"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold"><?= e((string) ($approvalItem['title'] ?? '')) ?></div>
                                        <div class="text-muted small"><?= e((string) ($approvalItem['type'] ?? 'Approval')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge text-bg-warning"><?= e((string) ($approvalItem['stage'] ?? 'Pending')) ?></span>
                            </td>
                            <td>
                                <div><?= e((string) ($approvalItem['subject'] ?? '')) ?></div>
                                <div class="text-muted small"><?= e((string) ($approvalItem['meta'] ?? '')) ?></div>
                            </td>
                            <td><?= e((string) ($approvalItem['created_at'] ?? '')) ?></td>
                            <td class="text-end">
                                <a href="<?= e((string) ($approvalItem['url'] ?? '#')) ?>" class="btn btn-primary btn-sm">
                                    <?= e((string) ($approvalItem['action_label'] ?? 'Review')) ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

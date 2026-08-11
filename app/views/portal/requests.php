<?php
$summary = $summary ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
$statusClass = static function (string $status): string {
    return match (strtolower($status)) {
        'approved', 'active', 'renewed', 'completed' => 'success',
        'rejected', 'cancelled', 'dismissed' => 'danger',
        'pending' => 'warning text-dark',
        default => 'secondary',
    };
};
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="text-dark mb-1">My Requests</h2>
        <p class="text-gray mb-0">Track requests you have submitted to HR, Finance, or Administration.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('portal/leave')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-calendar-plus me-1"></i>Apply Leave</a>
        <a href="<?= e(base_url('portal/salaryAdvance')) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-cash-coin me-1"></i>Request Advance</a>
        <a href="<?= e(base_url('portal/profile')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-lines-fill me-1"></i>Update Profile</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:#f59e0b">
            <span class="stat-label">Pending</span>
            <div class="stat-value"><?= (int) ($summary['pending'] ?? 0) ?></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted)">Awaiting review</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:#16a34a">
            <span class="stat-label">Approved</span>
            <div class="stat-value"><?= (int) ($summary['approved'] ?? 0) ?></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted)">Accepted or active</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:#dc2626">
            <span class="stat-label">Closed</span>
            <div class="stat-value"><?= (int) ($summary['rejected'] ?? 0) ?></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted)">Rejected, cancelled, or dismissed</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ent-stat-card h-100" style="--ent-stat-accent:#2563eb">
            <span class="stat-label">Total</span>
            <div class="stat-value"><?= (int) ($summary['total'] ?? 0) ?></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted)">All self-service requests</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Request History</h6>
            <span class="text-muted small"><?= count($items ?? []) ?> item(s)</span>
        </div>

        <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                <div class="fw-semibold text-dark">No requests yet</div>
                <div class="small">When you apply for leave, request an advance, update your profile, or request contract renewal, it will appear here.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Details</th>
                            <th>Submitted</th>
                            <th>Last Update</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $status = (string) ($item['status'] ?? 'Pending'); ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="stat-icon" style="width:34px;height:34px;background:#eff6ff;color:#2563eb"><i class="bi <?= e((string) ($item['icon'] ?? 'bi-send')) ?>"></i></span>
                                    <div>
                                        <div class="fw-semibold"><?= e((string) ($item['type'] ?? 'Request')) ?></div>
                                        <div class="text-muted small"><?= e((string) ($item['title'] ?? '')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="max-width:360px"><?= e((string) ($item['details'] ?? '')) ?></td>
                            <td><?= e(!empty($item['submitted_at']) ? date('d M Y', strtotime((string) $item['submitted_at'])) : '-') ?></td>
                            <td><?= e(!empty($item['updated_at']) ? date('d M Y', strtotime((string) $item['updated_at'])) : '-') ?></td>
                            <td class="text-center"><span class="badge bg-<?= e($statusClass($status)) ?>"><?= e($status) ?></span></td>
                            <td class="text-end">
                                <a href="<?= e((string) ($item['link'] ?? base_url('portal/dashboard'))) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Open
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

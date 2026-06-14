<div class="portal-page-header">
    <h2><i class="bi bi-diagram-3 me-2"></i>My Employment Lifecycle</h2>
    <p>Your employment status, key milestones, contract timeline, and HR-reviewed profile updates.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="portal-card h-100">
            <div class="text-muted small mb-1">Current Status</div>
            <div class="fw-bold fs-5"><?= e((string) ($employee['lifecycle_status'] ?? $employee['contract_status'] ?? 'Active')) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="portal-card h-100">
            <div class="text-muted small mb-1">Department</div>
            <div class="fw-bold fs-5"><?= e((string) ($employee['department_name'] ?? '-')) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="portal-card h-100">
            <div class="text-muted small mb-1">Probation End</div>
            <div class="fw-bold fs-5"><?= e(!empty($employee['probation_end_date']) ? date('d M Y', strtotime((string) $employee['probation_end_date'])) : 'Not set') ?></div>
        </div>
    </div>
</div>

<div class="portal-card mb-4">
    <h5 class="mb-3">Lifecycle History</h5>
    <?php if (empty($events)): ?>
        <div class="text-muted py-4 text-center">No lifecycle events have been recorded yet.</div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($events as $event): ?>
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between gap-3 flex-wrap">
                        <strong><?= e((string) ($event['event_type'] ?? 'Event')) ?></strong>
                        <span class="text-muted small"><?= e(!empty($event['effective_date']) ? date('d M Y', strtotime((string) $event['effective_date'])) : '-') ?></span>
                    </div>
                    <div class="text-muted small">
                        <?= e((string) ($event['from_status'] ?? '')) ?><?php if (!empty($event['to_status'])): ?> &rarr; <?= e((string) $event['to_status']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($event['to_department']) || !empty($event['to_designation'])): ?>
                        <div class="small mt-1"><?= e(trim((string) ($event['to_department'] ?? '') . ' ' . (string) ($event['to_designation'] ?? ''))) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($event['notes'])): ?>
                        <div class="text-muted small mt-1"><?= e((string) $event['notes']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="portal-card h-100">
            <h5 class="mb-3">Contract Timeline</h5>
            <?php if (empty($contracts)): ?>
                <div class="text-muted">No contracts found.</div>
            <?php else: ?>
                <?php foreach ($contracts as $contract): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e((string) ($contract['contract_type'] ?? 'Contract')) ?></strong>
                            <span class="badge bg-light text-dark border"><?= e((string) ($contract['approval_status'] ?? $contract['status'] ?? '')) ?></span>
                        </div>
                        <div class="text-muted small">
                            <?= e((string) ($contract['start_date'] ?? '-')) ?> to <?= e(!empty($contract['end_date']) ? (string) $contract['end_date'] : 'Open-ended') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="portal-card h-100">
            <h5 class="mb-3">Profile Update Requests</h5>
            <?php if (empty($profileRequests)): ?>
                <div class="text-muted">No profile change requests submitted.</div>
            <?php else: ?>
                <?php foreach ($profileRequests as $request): ?>
                    <?php $status = (string) ($request['status'] ?? 'Pending'); ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e(!empty($request['created_at']) ? date('d M Y', strtotime((string) $request['created_at'])) : 'Request') ?></strong>
                            <span class="badge bg-<?= $status === 'Approved' ? 'success' : ($status === 'Rejected' ? 'danger' : 'warning text-dark') ?>"><?= e($status) ?></span>
                        </div>
                        <?php if (!empty($request['review_notes'])): ?>
                            <div class="text-muted small mt-1"><?= e((string) $request['review_notes']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

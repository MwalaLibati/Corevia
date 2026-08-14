<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">Referral Leads</h4>
        <p class="text-muted mb-0 small">Submit prospects early so Corevia can track conversion and commission cleanly.</p>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

<?php
$stageCounts = [];
$openValue = 0.0;
$due = 0;
foreach (($leads ?? []) as $lead) {
    $stage = (string)($lead['stage'] ?? 'New');
    $stageCounts[$stage] = ($stageCounts[$stage] ?? 0) + 1;
    if (!in_array($stage, ['Won', 'Lost'], true)) {
        $openValue += (float)($lead['estimated_value'] ?? 0);
        if (!empty($lead['next_follow_up']) && strtotime((string)$lead['next_follow_up']) <= strtotime(date('Y-m-d'))) {
            $due++;
        }
    }
}
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Total Leads</span><div class="stat-value"><?= e((string)count($leads ?? [])) ?></div><div class="small text-muted">Submitted prospects</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Open Pipeline</span><div class="stat-value"><?= e((string)array_sum(array_intersect_key($stageCounts, array_flip(['New','Contacted','Demo Scheduled','Negotiating'])))) ?></div><div class="small text-muted">Still in progress</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Pipeline Value</span><div class="stat-value" style="font-size:1.15rem">ZMW <?= number_format($openValue, 2) ?></div><div class="small text-muted">Estimated open value</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card h-100"><span class="stat-label">Follow-ups Due</span><div class="stat-value"><?= e((string)$due) ?></div><div class="small text-muted">Need attention</div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">New Lead</h6>
                <form method="post" action="<?= e(base_url('affiliate/dashboard/leadStore')) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="mb-3"><label class="form-label">Company Name</label><input name="company_name" class="form-control" required></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input name="contact_phone" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="contact_email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Industry</label><input name="industry" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Employees</label><input type="number" min="0" name="employee_count" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Estimated Value</label><input type="number" min="0" step="0.01" name="estimated_value" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Source</label><input name="source" class="form-control" placeholder="Referral, event, call"></div>
                        <div class="col-md-6"><label class="form-label">Next Follow-up</label><input type="date" name="next_follow_up" class="form-control"></div>
                    </div>
                    <div class="mt-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
                    <button class="btn text-white mt-3" style="background:#7c3aed"><i class="bi bi-plus-circle me-1"></i>Submit Lead</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Lead Pipeline</h6>
                    <span class="badge bg-light text-dark border"><?= e((string)($stageCounts['Won'] ?? 0)) ?> converted</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Company</th><th>Contact</th><th>Stage</th><th>Value</th><th>Follow-up</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <?php
                            $stage = (string)($lead['stage'] ?? 'New');
                            $badgeClass = match ($stage) {
                                'Won' => 'bg-success',
                                'Lost' => 'bg-danger',
                                'Demo Scheduled' => 'bg-info text-dark',
                                'Negotiating' => 'bg-warning text-dark',
                                default => 'bg-light text-dark border',
                            };
                            ?>
                            <tr>
                                <td><strong><?= e((string)$lead['company_name']) ?></strong><div class="text-muted small"><?= e((string)($lead['industry'] ?? '')) ?></div></td>
                                <td><?= e((string)($lead['contact_person'] ?? '-')) ?><div class="text-muted small"><?= e((string)($lead['contact_phone'] ?? '')) ?></div></td>
                                <td><span class="badge <?= e($badgeClass) ?>"><?= e($stage) ?></span></td>
                                <td>ZMW <?= number_format((float)($lead['estimated_value'] ?? 0), 2) ?></td>
                                <td><?= e((string)($lead['next_follow_up'] ?? '-')) ?></td>
                                <td class="text-muted small"><?= e(strlen((string)($lead['notes'] ?? '')) > 70 ? substr((string)($lead['notes'] ?? ''), 0, 70) . '...' : (string)($lead['notes'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; if (empty($leads)): ?><tr><td colspan="6" class="text-center text-muted py-4">No leads submitted yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

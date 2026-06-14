<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">Referral Leads</h4>
        <p class="text-muted mb-0 small">Submit prospects early so Corevia can track conversion and commission cleanly.</p>
    </div>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>

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
                <h6 class="fw-bold mb-3">Lead Pipeline</h6>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Company</th><th>Contact</th><th>Stage</th><th>Value</th><th>Follow-up</th></tr></thead>
                        <tbody>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><strong><?= e((string)$lead['company_name']) ?></strong><div class="text-muted small"><?= e((string)($lead['industry'] ?? '')) ?></div></td>
                                <td><?= e((string)($lead['contact_person'] ?? '-')) ?><div class="text-muted small"><?= e((string)($lead['contact_phone'] ?? '')) ?></div></td>
                                <td><span class="badge bg-light text-dark border"><?= e((string)$lead['stage']) ?></span></td>
                                <td>ZMW <?= number_format((float)($lead['estimated_value'] ?? 0), 2) ?></td>
                                <td><?= e((string)($lead['next_follow_up'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; if (empty($leads)): ?><tr><td colspan="5" class="text-center text-muted py-4">No leads submitted yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

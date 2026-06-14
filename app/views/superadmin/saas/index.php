<?php
$data = $data ?? [];
$summary = $data['summary'] ?? [];
$renewals = $data['renewals'] ?? [];
$trials = $data['trials'] ?? [];
$overdue = $data['overdue'] ?? [];
$usage = $data['usage'] ?? [];
$planChanges = $data['planChanges'] ?? [];
$events = $data['events'] ?? [];
$plans = $plans ?? [];
?>

<div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="text-dark mb-0 fw-bold">SaaS Operations</h2>
        <p class="text-muted mb-0 mt-1 small">Renewals, trials, overdue accounts, usage billing, and tenant controls.</p>
    </div>
    <form method="post" action="<?= e(base_url('superadmin/saas/runControls')) ?>" onsubmit="return confirm('Run overdue and trial controls now?')">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
        <button class="btn btn-sm text-white" style="background:#7c3aed">
            <i class="bi bi-shield-check me-1"></i>Run Account Controls
        </button>
    </form>
</div>

<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string) $flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string) $flashErr) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Active Companies', $summary['active_companies'] ?? 0, 'bi-buildings', '#dcfce7', '#059669'],
        ['Suspended', $summary['suspended_companies'] ?? 0, 'bi-pause-circle', '#fee2e2', '#dc2626'],
        ['Renewals Due', $summary['renewals_due'] ?? 0, 'bi-clock-history', '#fef3c7', '#d97706'],
        ['Expired Trials', $summary['trials_expired'] ?? 0, 'bi-hourglass-bottom', '#fee2e2', '#dc2626'],
        ['Overdue Invoices', $summary['overdue_invoices'] ?? 0, 'bi-exclamation-triangle', '#ffedd5', '#ea580c'],
        ['Outstanding', 'ZMW ' . number_format((float)($summary['outstanding_balance'] ?? 0), 0), 'bi-cash-stack', '#ede9fe', '#7c3aed'],
    ] as [$label, $value, $icon, $bg, $color]): ?>
    <div class="col-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.68rem;color:#64748b;font-weight:700;text-transform:uppercase"><?= e((string)$label) ?></span>
                    <span style="width:28px;height:28px;background:<?= e($bg) ?>;color:<?= e($color) ?>;border-radius:7px;display:flex;align-items:center;justify-content:center"><i class="bi <?= e($icon) ?>"></i></span>
                </div>
                <div style="font-size:1.2rem;font-weight:800;color:#0f172a"><?= e((string)$value) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between">
                <h6 class="fw-bold mb-0">Renewal Reminders</h6>
                <span class="badge bg-warning text-dark"><?= count($renewals) ?> due</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.82rem">
                        <thead class="table-light"><tr><th>Company</th><th>Plan</th><th>Ends</th><th>Reminder</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($renewals)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No subscriptions expiring in the next 30 days.</td></tr>
                        <?php else: foreach ($renewals as $row): ?>
                            <tr>
                                <td><strong><?= e((string)$row['company_name']) ?></strong><div class="text-muted small"><?= e((string)($row['company_email'] ?? '')) ?></div></td>
                                <td><span class="badge bg-secondary"><?= e((string)$row['plan']) ?></span></td>
                                <td><?= e((string)$row['ends_at']) ?><div class="text-danger small"><?= (int)$row['days_left'] ?> day(s) left</div></td>
                                <td><?= !empty($row['renewal_reminder_sent_at']) ? '<span class="badge bg-success">Recorded</span>' : '<span class="badge bg-light text-dark">Not recorded</span>' ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= e(base_url('superadmin/saas/markRenewalReminder/' . (string)$row['id'])) ?>">
                                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                        <button class="btn btn-sm btn-outline-primary">Record Reminder</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Usage-Based Billing Summary</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:.8rem">
                        <thead class="table-light"><tr><th>Company</th><th class="text-center">Employees</th><th class="text-center">Users</th><th class="text-center">Payroll</th><th class="text-center">Contracts</th><th class="text-end">Monthly</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($usage as $row):
                            $monthly = (string)($row['billing_model'] ?? 'per_user') === 'flat'
                                ? (float)($row['monthly_rate'] ?? 0)
                                : (float)($row['monthly_rate'] ?? 0) * (int)($row['employee_count'] ?? 0);
                        ?>
                        <tr>
                            <td><strong><?= e((string)$row['name']) ?></strong><div class="text-muted small"><?= e((string)($row['plan'] ?? $row['subscription_plan'] ?? 'No active plan')) ?></div></td>
                            <td class="text-center"><?= (int)$row['employee_count'] ?><?php if ((int)($row['employee_count'] ?? 0) > (int)($row['billed_employees'] ?? 0) && !empty($row['subscription_id'])): ?> <i class="bi bi-arrow-up-circle-fill text-warning" title="Usage above billed employees"></i><?php endif; ?></td>
                            <td class="text-center"><?= (int)$row['user_count'] ?></td>
                            <td class="text-center"><?= (int)$row['payroll_runs'] ?></td>
                            <td class="text-center"><?= (int)$row['contracts'] ?></td>
                            <td class="text-end"><?= e((string)($row['currency'] ?? 'ZMW')) ?> <?= number_format($monthly, 2) ?></td>
                            <td class="text-end">
                                <?php if (!empty($row['subscription_id'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#planChange<?= (int)$row['subscription_id'] ?>">Plan Change</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0 text-danger">Overdue & Trial Controls</h6></div>
            <div class="card-body">
                <h6 class="small text-uppercase text-muted">Overdue Accounts</h6>
                <?php if (empty($overdue)): ?>
                    <p class="text-muted small">No overdue accounts.</p>
                <?php else: foreach ($overdue as $row): ?>
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e((string)$row['company_name']) ?></strong>
                            <span class="text-danger">ZMW <?= number_format((float)$row['overdue_balance'], 2) ?></span>
                        </div>
                        <div class="small text-muted"><?= (int)$row['days_overdue'] ?> day(s) overdue / <?= (int)$row['overdue_count'] ?> invoice(s)</div>
                        <div class="d-flex gap-2 mt-2">
                            <form method="post" action="<?= e(base_url('superadmin/saas/suspend/' . (string)$row['company_id'])) ?>">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <input type="hidden" name="reason" value="Suspended due to overdue subscription invoice.">
                                <button class="btn btn-sm btn-outline-danger">Suspend</button>
                            </form>
                            <form method="post" action="<?= e(base_url('superadmin/saas/reactivate/' . (string)$row['company_id'])) ?>">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <input type="hidden" name="reason" value="Reactivated after billing review.">
                                <button class="btn btn-sm btn-outline-success">Reactivate</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; endif; ?>

                <h6 class="small text-uppercase text-muted mt-4">Trial Expiry</h6>
                <?php if (empty($trials)): ?>
                    <p class="text-muted small">No trials expiring in the next 14 days.</p>
                <?php else: foreach ($trials as $trial): ?>
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between"><strong><?= e((string)$trial['name']) ?></strong><span class="<?= (int)$trial['days_left'] < 0 ? 'text-danger' : 'text-warning' ?>"><?= (int)$trial['days_left'] ?>d</span></div>
                        <div class="small text-muted">Trial ends: <?= e((string)$trial['trial_ends_at']) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Plan Change Workflow</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" style="font-size:.78rem">
                        <thead class="table-light"><tr><th>Company</th><th>Change</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($planChanges)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No plan changes recorded.</td></tr>
                        <?php else: foreach ($planChanges as $change): ?>
                            <tr>
                                <td><?= e((string)$change['company_name']) ?></td>
                                <td><?= e((string)$change['current_plan']) ?> -> <?= e((string)$change['requested_plan']) ?><div class="text-muted"><?= e((string)$change['change_type']) ?></div></td>
                                <td><span class="badge bg-<?= (string)$change['status'] === 'Pending' ? 'warning' : ((string)$change['status'] === 'Applied' ? 'success' : 'secondary') ?>"><?= e((string)$change['status']) ?></span></td>
                                <td class="text-end">
                                    <?php if ((string)$change['status'] === 'Pending'): ?>
                                    <form method="post" action="<?= e(base_url('superadmin/saas/applyPlanChange/' . (string)$change['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                        <button class="btn btn-sm btn-outline-success">Apply</button>
                                    </form>
                                    <form method="post" action="<?= e(base_url('superadmin/saas/rejectPlanChange/' . (string)$change['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3"><h6 class="fw-bold mb-0">Recent SaaS Events</h6></div>
            <div class="card-body">
                <?php foreach ($events as $event): ?>
                    <div class="border-bottom py-2">
                        <div class="fw-semibold small"><?= e((string)$event['message']) ?></div>
                        <div class="text-muted" style="font-size:.72rem"><?= e((string)($event['company_name'] ?? 'Platform')) ?> / <?= e((string)$event['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php foreach ($usage as $row): if (empty($row['subscription_id'])) { continue; } ?>
<div class="modal fade" id="planChange<?= (int)$row['subscription_id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="<?= e(base_url('superadmin/saas/requestPlanChange')) ?>" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <input type="hidden" name="subscription_id" value="<?= (int)$row['subscription_id'] ?>">
            <div class="modal-header"><h5 class="modal-title">Plan Change - <?= e((string)$row['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Target Plan</label>
                    <select name="requested_plan" class="form-select" required>
                        <?php foreach ($plans as $plan): ?>
                            <option value="<?= e((string)$plan['name']) ?>"><?= e((string)$plan['name']) ?> - <?= e((string)$plan['currency']) ?> <?= number_format((float)$plan['default_monthly_rate'], 2) ?>/month</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Billing Model</label><select name="billing_model" class="form-select"><option value="per_user">Per user</option><option value="flat">Flat</option></select></div>
                    <div class="col-md-6"><label class="form-label">Cycle</label><select name="billing_cycle" class="form-select"><option>Annual</option><option>Monthly</option></select></div>
                    <div class="col-md-6"><label class="form-label">Monthly Rate</label><input type="number" step="0.01" min="0" name="monthly_rate" class="form-control" placeholder="Use plan default"></div>
                    <div class="col-md-6"><label class="form-label">Effective Date</label><input type="date" name="effective_date" class="form-control" value="<?= e(date('Y-m-d')) ?>"></div>
                </div>
                <div class="mt-3"><label class="form-label">Reason</label><textarea name="reason" rows="2" class="form-control"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn text-white" style="background:#7c3aed">Create Request</button></div>
        </form>
    </div>
</div>
<?php endforeach; ?>

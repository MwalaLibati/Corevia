<?php
$s = $dashboard['summary'] ?? [];
$companies = $dashboard['companies'] ?? [];
$commissions = $dashboard['commissions'] ?? [];
$monthly = $dashboard['monthly'] ?? [];
$leadStages = $dashboard['lead_stages'] ?? [];
$dueFollowups = $dashboard['due_followups'] ?? [];
$readiness = $readiness ?? ['score' => 0, 'missing' => []];
$payouts = $payouts ?? [];
$pendingPayout = (float)(($s['pending_commission'] ?? 0) + ($s['approved_commission'] ?? 0));
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="mb-0">Welcome, <?= e((string)($affiliate['full_name'] ?? 'Affiliate')) ?></h4>
        <p class="text-muted mb-0 small">Referral code: <strong><?= e((string)($affiliate['affiliate_code'] ?? '')) ?></strong></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('affiliate/dashboard/leads')) ?>" class="btn btn-sm text-white" style="background:#7c3aed"><i class="bi bi-plus-circle me-1"></i>Submit Lead</a>
        <a href="<?= e(base_url('affiliate/dashboard/statement')) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Statement</a>
    </div>
</div>

<?php if (empty($ready)): ?><div class="alert alert-warning">Affiliate portal is not installed yet. Please contact Corevia support.</div><?php endif; ?>
<?php if ((int)($readiness['score'] ?? 0) < 100): ?>
    <div class="alert border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#fff7ed;color:#9a3412">
        <div>
            <strong>Profile readiness: <?= e((string)($readiness['score'] ?? 0)) ?>%</strong>
            <div class="small">Complete your KYC and payout information so commissions can be paid without delays.</div>
        </div>
        <a href="<?= e(base_url('affiliate/dashboard/profile')) ?>" class="btn btn-sm btn-outline-warning">Complete Profile</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Companies Brought', (string)($s['company_count'] ?? 0), 'bi-buildings', '#dbeafe', '#2563eb'],
        ['Open Leads', (string)($s['open_leads'] ?? 0), 'bi-funnel', '#fef9c3', '#ca8a04'],
        ['Conversion Rate', number_format((float)($s['conversion_rate'] ?? 0), 1).'%', 'bi-award', '#dcfce7', '#16a34a'],
        ['Active Companies', (string)($s['active_companies'] ?? 0), 'bi-check2-circle', '#dcfce7', '#16a34a'],
        ['This Year', 'ZMW '.number_format((float)($s['current_year_commission'] ?? 0), 2), 'bi-calendar2-check', '#ede9fe', '#7c3aed'],
        ['Pending Payout', 'ZMW '.number_format($pendingPayout, 2), 'bi-wallet2', '#fef3c7', '#d97706'],
        ['Paid To Date', 'ZMW '.number_format((float)($s['paid_commission'] ?? 0), 2), 'bi-cash-stack', '#ccfbf1', '#0f766e'],
        ['Pipeline Value', 'ZMW '.number_format((float)($s['estimated_pipeline_value'] ?? 0), 2), 'bi-graph-up-arrow', '#fee2e2', '#dc2626'],
    ] as $card): ?>
    <div class="col-sm-6 col-xl-4"><div class="ent-stat-card h-100" style="--ent-stat-accent:<?= e($card[4]) ?>"><span class="stat-icon" style="background:<?= e($card[3]) ?>;color:<?= e($card[4]) ?>"><i class="bi <?= e($card[2]) ?>"></i></span><span class="stat-label"><?= e($card[0]) ?></span><div class="stat-value" style="font-size:1.2rem"><?= e($card[1]) ?></div></div></div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Action Centre</h6>
                    <span class="badge bg-light text-dark border"><?= e((string)($s['due_followups'] ?? 0)) ?> due</span>
                </div>
                <?php if (!empty($dueFollowups)): ?>
                    <?php foreach ($dueFollowups as $lead): ?>
                        <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                            <div><strong><?= e((string)$lead['company_name']) ?></strong><div class="text-muted small"><?= e((string)($lead['stage'] ?? '')) ?> &bull; <?= e((string)($lead['contact_person'] ?? 'No contact person')) ?></div></div>
                            <span class="small text-danger"><?= e((string)$lead['next_follow_up']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php elseif ((int)($readiness['score'] ?? 0) < 100): ?>
                    <p class="text-muted mb-0">Your next best action is completing profile/KYC requirements.</p>
                <?php else: ?>
                    <p class="text-muted mb-0">No follow-ups are overdue. Your affiliate account is up to date.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Lead Pipeline by Stage</h6>
                <?php foreach ($leadStages as $stage): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span><?= e((string)$stage['stage']) ?></span>
                        <strong><?= e((string)$stage['total']) ?></strong>
                    </div>
                <?php endforeach; if (empty($leadStages)): ?>
                    <p class="text-muted text-center py-4 mb-0">No leads submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Payout Snapshot</h6>
                <div class="text-muted small">Available / awaiting processing</div>
                <div class="h4 mb-3">ZMW <?= number_format($pendingPayout, 2) ?></div>
                <?php $latestPayout = $payouts[0] ?? null; ?>
                <?php if ($latestPayout): ?>
                    <div class="border-top pt-3 small">
                        <strong><?= e((string)$latestPayout['payout_reference']) ?></strong>
                        <div class="text-muted"><?= e((string)$latestPayout['status']) ?> &bull; ZMW <?= number_format((float)$latestPayout['net_amount'], 2) ?></div>
                    </div>
                <?php else: ?>
                    <div class="text-muted small border-top pt-3">No payout batch has been created yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Referral Pipeline</h6>
            <a href="<?= e(base_url('affiliate/dashboard/leads')) ?>" class="btn btn-sm btn-outline-secondary">Manage Leads</a>
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Company</th><th>Contact</th><th>Stage</th><th>Follow-up</th></tr></thead>
            <tbody>
            <?php foreach (($dashboard['leads'] ?? []) as $lead): ?>
                <tr><td><strong><?= e((string)$lead['company_name']) ?></strong><div class="text-muted small"><?= e((string)($lead['industry'] ?? '')) ?></div></td><td><?= e((string)($lead['contact_person'] ?? '-')) ?><div class="text-muted small"><?= e((string)($lead['contact_phone'] ?? '')) ?></div></td><td><span class="badge bg-light text-dark border"><?= e((string)$lead['stage']) ?></span></td><td><?= e((string)($lead['next_follow_up'] ?? '-')) ?></td></tr>
            <?php endforeach; if (empty($dashboard['leads'])): ?><tr><td colspan="4" class="text-center text-muted py-4">No referral leads submitted yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Companies Earning Commission</h6>
                <div class="table-responsive"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Company</th><th>Status</th><th>Total Earned</th><th>Unpaid</th></tr></thead>
                    <tbody>
                    <?php foreach ($companies as $c): ?>
                        <tr><td><strong><?= e((string)$c['company_name']) ?></strong><div class="text-muted small"><?= e((string)$c['company_email']) ?></div></td><td><?= e((string)$c['referral_status']) ?></td><td>ZMW <?= number_format((float)$c['total_commission'], 2) ?></td><td>ZMW <?= number_format((float)$c['unpaid_commission'], 2) ?></td></tr>
                    <?php endforeach; if (empty($companies)): ?><tr><td colspan="4" class="text-center text-muted py-4">No referred companies yet.</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Monthly Earnings</h6>
                <?php if (empty($monthly)): ?>
                    <p class="text-muted text-center py-4 mb-0">No commission trend yet.</p>
                <?php else: foreach ($monthly as $m): ?>
                    <div class="d-flex justify-content-between border-bottom py-2"><span><?= e((string)$m['period']) ?></span><strong>ZMW <?= number_format((float)$m['amount'], 2) ?></strong></div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">Recent Commission Activity</h6>
            <a href="<?= e(base_url('affiliate/dashboard/commissions')) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead><tr><th>Date</th><th>Company</th><th>Invoice</th><th>Payment</th><th>Commission</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($commissions, 0, 8) as $c): ?>
                <tr><td><?= e((string)$c['earned_at']) ?></td><td><?= e((string)$c['company_name']) ?></td><td><?= e((string)$c['invoice_number']) ?></td><td>ZMW <?= number_format((float)$c['payment_amount'], 2) ?></td><td>ZMW <?= number_format((float)$c['commission_amount'], 2) ?></td><td><?= e((string)$c['status']) ?></td></tr>
            <?php endforeach; if (empty($commissions)): ?><tr><td colspan="6" class="text-center text-muted py-4">No commission has been earned yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
</div>

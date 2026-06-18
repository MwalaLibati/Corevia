<?php
$emp = $employee ?? [];
$pay = $payrollSummary ?? [];
$leave = $leaveSummary ?? [];
$contract = $activeContract ?? null;
$salary = $activeSalary ?? null;
$advance = $activeAdvance ?? null;
$lifecycle = $lifecycleHistory ?? [];
$eventTypes = $lifecycleEventTypes ?? [];
$departmentOptions = $departments ?? [];
$onboardingChecklist = $onboardingChecklist ?? [];
$exitChecklist = $exitChecklist ?? [];
$reminders = $lifecycleReminders ?? [];
$disciplinaryRecords = $disciplinaryRecords ?? [];
$finalDue = $finalDue ?? null;
$generatedLetters = $generatedLetters ?? [];
$letterTypes = $letterTypes ?? ['Employment Certificate', 'Promotion Letter', 'Transfer Letter', 'Confirmation Letter', 'Termination Letter', 'Final Dues Statement'];
$letterTypeIcons = [
    'Employment Certificate' => 'bi-award',
    'Promotion Letter' => 'bi-graph-up-arrow',
    'Transfer Letter' => 'bi-arrow-left-right',
    'Confirmation Letter' => 'bi-patch-check',
    'Termination Letter' => 'bi-door-open',
    'Final Dues Statement' => 'bi-receipt-cutoff',
];
$gratuity = $gratuityEstimate ?? [
    'eligible' => false,
    'amount' => 0,
    'accrued_amount' => 0,
    'months' => 0,
    'years' => 0,
    'rate' => 5,
    'qualifying_years' => 2,
    'payment_timing' => 'End of contract',
];

$status = (string) ($emp['lifecycle_status'] ?? $emp['contract_status'] ?? 'Active');
$statusClass = ['Active' => 'success', 'Probation' => 'info', 'Ended' => 'secondary', 'Suspended' => 'warning', 'Terminated' => 'secondary'][$status] ?? 'secondary';
$canEditEmployee = in_array((string) (current_user()['role'] ?? ''), ['Super Admin', 'HR Officer'], true)
    || in_array((string) (current_user()['access_level'] ?? ''), ['Super Admin', 'HR Officer'], true);

$profileField = static function (string $label, mixed $value): void {
    $display = trim((string) ($value ?? ''));
    echo '<div class="profile-field">';
    echo '<div class="profile-field-label">' . e($label) . '</div>';
    echo '<div class="profile-field-value">' . ($display !== '' ? e($display) : '<span class="text-gray">Not set</span>') . '</div>';
    echo '</div>';
};
?>

<style>
    .employee-profile-hero {
        background: #0f172a;
        border-radius: 8px;
        padding: 24px;
        color: #fff;
    }
    .employee-avatar {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        background: rgba(255,255,255,.12);
        font-weight: 800;
        font-size: 1.35rem;
        flex: 0 0 auto;
    }
    .profile-stat-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        background: #fff;
        min-height: 126px;
    }
    .profile-stat-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .profile-stat-value {
        color: #111827;
        font-size: 1.35rem;
        font-weight: 800;
        margin-top: 8px;
        word-break: break-word;
    }
    .profile-field {
        border-bottom: 1px solid #f1f5f9;
        padding: 12px 0;
    }
    .profile-field-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
        margin-bottom: 3px;
    }
    .profile-field-value {
        color: #111827;
        font-weight: 600;
    }
    .lifecycle-timeline {
        display: grid;
        gap: 14px;
    }
    .lifecycle-item {
        border-left: 3px solid #2563eb;
        padding: 2px 0 12px 16px;
    }
    .lifecycle-meta {
        color: #64748b;
        font-size: .82rem;
    }
    .letter-action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        gap: 10px;
    }
    .letter-action-btn {
        width: 100%;
        min-height: 52px;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        background: #fff;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        font-weight: 700;
        text-align: left;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .letter-action-btn:hover {
        border-color: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .12);
        transform: translateY(-1px);
    }
    .letter-action-btn i {
        color: #2563eb;
        font-size: 1.1rem;
    }
    .profile-detail-row {
        margin-bottom: 1.75rem;
    }
    .profile-detail-row .card-body,
    .profile-lifecycle-row .card-body {
        padding: 28px;
    }
    .profile-lifecycle-row {
        row-gap: 24px;
    }
    .profile-lifecycle-row .form-label {
        font-weight: 700;
        margin-bottom: 8px;
    }
    .profile-lifecycle-row .form-control,
    .profile-lifecycle-row .form-select {
        min-height: 48px;
    }
    .profile-lifecycle-row textarea.form-control {
        min-height: 96px;
    }
    @media (max-width: 767.98px) {
        .profile-detail-row .card-body,
        .profile-lifecycle-row .card-body {
            padding: 22px;
        }
    }
</style>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Employee Profile</h2>
        <p class="text-gray mb-0">Payroll, leave, contract, and employment snapshot.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEditEmployee): ?>
        <a href="<?= e(base_url('employee/edit/' . (string) $emp['id'])) ?>" class="btn btn-primary">Edit Employee</a>
        <a href="#employeeLetters" class="btn btn-outline-primary">Generate Letter</a>
        <?php endif; ?>
        <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="employee-profile-hero mb-4">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="employee-avatar">
            <?= e(strtoupper(substr((string) ($emp['full_name'] ?? 'E'), 0, 1))) ?>
        </div>
        <div class="flex-grow-1">
            <h3 class="mb-1 text-white"><?= e((string) ($emp['full_name'] ?? 'Employee')) ?></h3>
            <div style="color:rgba(255,255,255,.76)">
                <?= e((string) ($emp['employee_number'] ?? '')) ?>
                <?php if (!empty($emp['designation'])): ?>
                    &middot; <?= e((string) $emp['designation']) ?>
                <?php endif; ?>
                <?php if (!empty($emp['department_name'])): ?>
                    &middot; <?= e((string) $emp['department_name']) ?>
                <?php endif; ?>
                <?php if (!empty($emp['branch_name'])): ?>
                    &middot; <?= e((string) $emp['branch_name']) ?>
                <?php endif; ?>
            </div>
        </div>
        <span class="badge bg-<?= e($statusClass) ?> px-3 py-2"><?= e($status) ?></span>
    </div>
</div>

<?php if ($canEditEmployee): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h5 class="mb-1">Employee Portal Access</h5>
            <p class="text-gray mb-2" style="font-size:.86rem">
                Manage the employee's self-service login, one-time password, and portal access status.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <?php if ((int)($emp['portal_active'] ?? 0) === 1): ?>
                    <span class="badge bg-success">Portal Enabled</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Portal Disabled</span>
                <?php endif; ?>
                <?php if (!empty($emp['portal_must_change_password'])): ?>
                    <span class="badge bg-warning text-dark">Must Change Password</span>
                <?php endif; ?>
                <?php if (!empty($emp['portal_password_expires_at'])): ?>
                    <span class="badge bg-light text-dark border">OTP expires: <?= e((string)$emp['portal_password_expires_at']) ?></span>
                <?php endif; ?>
                <?php if (!empty($emp['portal_last_login_at'])): ?>
                    <span class="badge bg-light text-dark border">Last login: <?= e((string)$emp['portal_last_login_at']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <form method="post" action="<?= e(base_url('employee/portalAccess/' . (string)$emp['id'])) ?>">
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                <input type="hidden" name="action" value="generate">
                <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Generate a new one-time password for this employee? The employee must change it on first login.');">
                    <?= (int)($emp['portal_active'] ?? 0) === 1 ? 'Reset One-Time Password' : 'Enable Portal Access' ?>
                </button>
            </form>
            <?php if ((int)($emp['portal_active'] ?? 0) === 1): ?>
                <form method="post" action="<?= e(base_url('employee/portalAccess/' . (string)$emp['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Deactivate this employee portal account?');">Deactivate Portal</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="profile-stat-card">
            <div class="profile-stat-label">Paid So Far</div>
            <div class="profile-stat-value text-success"><?= e(format_currency((float) ($pay['paid_so_far'] ?? 0))) ?></div>
            <div class="text-gray small mt-2">Allocated from paid payroll runs</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="profile-stat-card">
            <div class="profile-stat-label">Not Paid Yet</div>
            <div class="profile-stat-value text-danger"><?= e(format_currency((float) ($pay['outstanding'] ?? 0))) ?></div>
            <div class="text-gray small mt-2">Unpaid net pay across payroll</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="profile-stat-card">
            <div class="profile-stat-label">Leave Due</div>
            <div class="profile-stat-value"><?= e(number_format((float) ($leave['balance_days'] ?? 0), 1)) ?> days</div>
            <div class="text-gray small mt-2"><?= e((string) ($leave['year'] ?? date('Y'))) ?> balance after approved usage</div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="profile-stat-card">
            <div class="profile-stat-label">Accumulating Gratuity</div>
            <div class="profile-stat-value"><?= e(format_currency((float) ($gratuity['accrued_amount'] ?? 0))) ?></div>
            <div class="text-gray small mt-2">
                <?= e((string) ($gratuity['rate'] ?? 5)) ?>% of basic annual salary earned over <?= e(number_format((float) ($gratuity['years'] ?? 0), 2)) ?> year(s)
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div>
            <h5 class="mb-1">Payable Gratuity Estimate</h5>
            <p class="text-gray mb-0" style="font-size:.86rem">
                <?php if (!empty($gratuity['eligible'])): ?>
                    Employee has met the <?= e((string) ($gratuity['qualifying_years'] ?? 2)) ?> year qualifying period.
                <?php else: ?>
                    Accumulating, but payable only after <?= e((string) ($gratuity['qualifying_years'] ?? 2)) ?> year(s) of contract service.
                <?php endif; ?>
                Payment timing: <?= e((string) ($gratuity['payment_timing'] ?? 'End of contract')) ?>.
            </p>
        </div>
        <div class="text-end">
            <div class="profile-stat-label">Currently Payable</div>
            <div class="profile-stat-value <?= !empty($gratuity['eligible']) ? 'text-success' : 'text-gray' ?>">
                <?= e(format_currency((float) ($gratuity['amount'] ?? 0))) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Onboarding Checklist</h5>
                <?php foreach ($onboardingChecklist as $item): ?>
                    <form method="post" action="<?= e(base_url('employee/checklistToggle/' . (string)$item['id'])) ?>" class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                        <input type="hidden" name="employee_id" value="<?= e((string)$emp['id']) ?>">
                        <span><?= (int)$item['is_completed'] === 1 ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' : '<i class="bi bi-circle text-muted me-1"></i>' ?><?= e((string)$item['item_label']) ?></span>
                        <?php if ($canEditEmployee): ?><button class="btn btn-sm btn-outline-primary" type="submit"><?= (int)$item['is_completed'] === 1 ? 'Undo' : 'Done' ?></button><?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Exit Checklist</h5>
                <?php foreach ($exitChecklist as $item): ?>
                    <form method="post" action="<?= e(base_url('employee/checklistToggle/' . (string)$item['id'])) ?>" class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                        <input type="hidden" name="employee_id" value="<?= e((string)$emp['id']) ?>">
                        <span><?= (int)$item['is_completed'] === 1 ? '<i class="bi bi-check-circle-fill text-success me-1"></i>' : '<i class="bi bi-circle text-muted me-1"></i>' ?><?= e((string)$item['item_label']) ?></span>
                        <?php if ($canEditEmployee): ?><button class="btn btn-sm btn-outline-primary" type="submit"><?= (int)$item['is_completed'] === 1 ? 'Undo' : 'Done' ?></button><?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Lifecycle Reminders</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Reminder</th><th>Employee</th><th>Due Date</th><th>Designation</th></tr></thead>
                <tbody>
                <?php if (empty($reminders)): ?>
                    <tr><td colspan="4" class="text-center text-gray">No probation or contract expiry reminders due in the next 45 days.</td></tr>
                <?php else: foreach ($reminders as $reminder): ?>
                    <tr>
                        <td><span class="badge bg-warning text-dark"><?= e((string)$reminder['reminder_type']) ?></span></td>
                        <td><?= e((string)$reminder['full_name']) ?> <span class="text-gray small"><?= e((string)$reminder['employee_number']) ?></span></td>
                        <td><?= e((string)$reminder['due_date']) ?></td>
                        <td><?= e((string)($reminder['designation'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 profile-detail-row">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Employment Details</h5>
                <?php $profileField('Email', $emp['email'] ?? null); ?>
                <?php $profileField('Phone', $emp['phone'] ?? null); ?>
                <?php $profileField('Client Entity', $emp['client_entity_name'] ?? null); ?>
                <?php $profileField('Company', $emp['company_name'] ?? null); ?>
                <?php $profileField('Branch / Location', $emp['branch_name'] ?? null); ?>
                <?php $profileField('Employment Type', $emp['employment_type'] ?? null); ?>
                <?php $profileField('Department', $emp['department_name'] ?? null); ?>
                <?php $profileField('Designation', $emp['designation'] ?? null); ?>
                <?php $profileField('Hire Date', !empty($emp['hired_at']) ? format_date((string) $emp['hired_at']) : null); ?>
                <?php $profileField('Bank', trim((string)($emp['bank_name'] ?? '') . ' ' . (string)($emp['bank_account_number'] ?? ''))); ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Current Package</h5>
                <?php if ($salary): ?>
                    <?php $profileField('Salary Structure', ($salary['structure_name'] ?? '') . (!empty($salary['grade_level']) ? ' - ' . $salary['grade_level'] : '')); ?>
                    <?php $profileField('Basic Pay', format_currency((float) ($salary['basic_pay'] ?? 0))); ?>
                    <?php $profileField('Allowances', format_currency((float)($salary['housing_allowance'] ?? 0) + (float)($salary['transport_allowance'] ?? 0) + (float)($salary['other_allowances'] ?? 0))); ?>
                    <?php $profileField('Effective From', !empty($salary['effective_date']) ? format_date((string) $salary['effective_date']) : null); ?>
                <?php else: ?>
                    <div class="text-gray">No active salary structure assigned.</div>
                <?php endif; ?>
                <hr>
                <h6 class="mb-2">Salary Advance</h6>
                <?php if ($advance): ?>
                    <?php $profileField('Outstanding', format_currency((float) ($advance['outstanding_balance'] ?? 0))); ?>
                    <?php $profileField('Monthly Deduction', format_currency((float) ($advance['monthly_deduction'] ?? 0))); ?>
                <?php else: ?>
                    <div class="text-gray">No active salary advance.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Active Contract</h5>
                <?php if ($contract): ?>
                    <?php $profileField('Contract Number', $contract['contract_number'] ?? null); ?>
                    <?php $profileField('Type', $contract['contract_type'] ?? null); ?>
                    <?php $profileField('Start Date', !empty($contract['start_date']) ? format_date((string) $contract['start_date']) : null); ?>
                    <?php $profileField('End Date', !empty($contract['end_date']) ? format_date((string) $contract['end_date']) : 'Open-ended'); ?>
                    <?php $profileField('Status', $contract['status'] ?? null); ?>
                <?php else: ?>
                    <div class="text-gray">No active contract found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($profileChangeRequests)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h5 class="mb-3">Pending Profile Change Requests</h5>
        <?php foreach ($profileChangeRequests as $request): ?>
            <?php
            $changes = json_decode((string) ($request['requested_changes_json'] ?? '{}'), true);
            $changes = is_array($changes) ? $changes : [];
            ?>
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-2">
                    <div>
                        <strong><?= e((string) ($request['full_name'] ?? 'Employee')) ?></strong>
                        <div class="text-gray small">Requested <?= e(!empty($request['created_at']) ? format_date((string) $request['created_at']) : '-') ?></div>
                    </div>
                    <span class="badge bg-warning text-dark">Pending HR Review</span>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Field</th><th>Current</th><th>Requested</th></tr></thead>
                        <tbody>
                        <?php foreach ($changes as $field => $change): ?>
                            <tr>
                                <td><?= e(ucwords(str_replace('_', ' ', (string) $field))) ?></td>
                                <td class="text-gray"><?= e((string) ($change['old'] ?? '')) ?></td>
                                <td><strong><?= e((string) ($change['new'] ?? '')) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="post" action="<?= e(base_url('employee/profileChangeReview/' . (string) $emp['id'] . '/' . (string) $request['id'])) ?>">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-sm btn-success" type="submit">Approve Changes</button>
                    </form>
                    <form method="post" action="<?= e(base_url('employee/profileChangeReview/' . (string) $emp['id'] . '/' . (string) $request['id'])) ?>" class="d-flex gap-2">
                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Reason, optional">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4 profile-lifecycle-row">
    <div class="<?= $canEditEmployee ? 'col-xl-5' : 'col-12' ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Employment Lifecycle</h5>
                <?php if (empty($lifecycle)): ?>
                    <div class="text-gray">No lifecycle events recorded yet.</div>
                <?php else: ?>
                    <div class="lifecycle-timeline">
                        <?php foreach ($lifecycle as $event): ?>
                            <div class="lifecycle-item">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                    <strong><?= e((string) ($event['event_type'] ?? 'Event')) ?></strong>
                                    <span class="lifecycle-meta"><?= !empty($event['effective_date']) ? e(format_date((string) $event['effective_date'])) : '-' ?></span>
                                </div>
                                <div class="lifecycle-meta">
                                    <?= e((string) ($event['from_status'] ?? '')) ?>
                                    <?php if (!empty($event['to_status'])): ?>
                                        &rarr; <?= e((string) $event['to_status']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($event['to_department']) || !empty($event['to_designation'])): ?>
                                    <div class="small mt-1">
                                        <?= e(trim((string) ($event['to_department'] ?? '') . ' ' . (string) ($event['to_designation'] ?? ''))) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($event['notes'])): ?>
                                    <div class="small text-gray mt-1"><?= e((string) $event['notes']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($canEditEmployee): ?>
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Record Lifecycle Event</h5>
                <form method="post" action="<?= e(base_url('employee/lifecycleEvent/' . (string) ($emp['id'] ?? 0))) ?>">
                    <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Event Type</label>
                            <select name="event_type" class="form-control" required>
                                <?php foreach ($eventTypes as $type): ?>
                                    <option value="<?= e((string) $type) ?>"><?= e((string) $type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Effective Date</label>
                            <input type="date" name="effective_date" class="form-control" value="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Department</label>
                            <select name="to_department_id" class="form-control">
                                <option value="0">No department change</option>
                                <?php foreach ($departmentOptions as $dept): ?>
                                    <option value="<?= e((string) ($dept['id'] ?? 0)) ?>"><?= e((string) ($dept['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Designation</label>
                            <input type="text" name="to_designation" class="form-control" placeholder="Leave blank if unchanged">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Probation End Date</label>
                            <input type="date" name="probation_end_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Optional internal note"></textarea>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4 mt-1 mb-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Disciplinary Records</h5>
                <?php if ($canEditEmployee): ?>
                <form method="post" action="<?= e(base_url('employee/disciplinaryStore/' . (string)$emp['id'])) ?>" class="row g-2 mb-3">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="col-md-4"><input type="date" name="incident_date" class="form-control" value="<?= e(date('Y-m-d')) ?>"></div>
                    <div class="col-md-4"><select name="record_type" class="form-select"><option>Verbal Warning</option><option>Written Warning</option><option>Final Warning</option><option>Suspension</option><option>Hearing</option><option>Other</option></select></div>
                    <div class="col-md-4"><select name="severity" class="form-select"><option>Low</option><option selected>Medium</option><option>High</option><option>Critical</option></select></div>
                    <div class="col-12"><input type="text" name="subject" class="form-control" placeholder="Subject"></div>
                    <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea></div>
                    <div class="col-12"><textarea name="action_taken" class="form-control" rows="2" placeholder="Action taken"></textarea></div>
                    <div class="col-12 text-end"><button class="btn btn-outline-primary btn-sm">Save Record</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th>Severity</th><th>Subject</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (empty($disciplinaryRecords)): ?>
                            <tr><td colspan="5" class="text-center text-gray">No disciplinary records.</td></tr>
                        <?php else: foreach ($disciplinaryRecords as $record): ?>
                            <tr><td><?= e((string)$record['incident_date']) ?></td><td><?= e((string)$record['record_type']) ?></td><td><?= e((string)$record['severity']) ?></td><td><?= e((string)$record['subject']) ?></td><td><?= e((string)$record['status']) ?></td></tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3">Exit & Final Dues</h5>
                <?php if ($finalDue): ?>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><div class="profile-field-label">Unpaid Salary</div><strong><?= e(format_currency((float)$finalDue['unpaid_salary'])) ?></strong></div>
                        <div class="col-6"><div class="profile-field-label">Leave Pay</div><strong><?= e(format_currency((float)$finalDue['leave_pay'])) ?></strong></div>
                        <div class="col-6"><div class="profile-field-label">Gratuity</div><strong><?= e(format_currency((float)$finalDue['gratuity_pay'])) ?></strong></div>
                        <div class="col-6"><div class="profile-field-label">Net Final Due</div><strong class="text-success"><?= e(format_currency((float)$finalDue['net_final_due'])) ?></strong></div>
                    </div>
                <?php else: ?>
                    <p class="text-gray">No final dues calculation recorded yet.</p>
                <?php endif; ?>
                <?php if ($canEditEmployee): ?>
                <form method="post" action="<?= e(base_url('employee/finalDuesCalculate/' . (string)$emp['id'])) ?>" class="row g-2">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <div class="col-md-4"><input type="number" step="0.01" min="0" name="deductions" class="form-control" placeholder="Deductions"></div>
                    <div class="col-md-8"><input type="text" name="notes" class="form-control" placeholder="Notes"></div>
                    <div class="col-12 text-end"><button class="btn btn-outline-primary btn-sm">Calculate Final Dues</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4" id="employeeLetters">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">Certificates & Letters</h5>
                <p class="text-gray mb-0 small">Create employee certificates and HR letters from approved templates.</p>
            </div>
            <?php if ($canEditEmployee): ?>
            <a href="<?= e(base_url('employee-letter-template/index')) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-pencil-square me-1"></i>Manage Templates
            </a>
            <?php endif; ?>
        </div>

        <?php if ($canEditEmployee): ?>
        <div class="letter-action-grid mb-4">
            <?php foreach ($letterTypes as $type): ?>
                <form method="post" action="<?= e(base_url('employee/generateLetter/' . (string)$emp['id'])) ?>">
                    <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                    <input type="hidden" name="letter_type" value="<?= e((string)$type) ?>">
                    <button type="submit" class="letter-action-btn">
                        <i class="bi <?= e((string)($letterTypeIcons[(string)$type] ?? 'bi-file-earmark-text')) ?>"></i>
                        <span><?= e((string)$type) ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead><tr><th>Title</th><th>Type</th><th>Generated</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php if (empty($generatedLetters)): ?>
                    <tr><td colspan="4" class="text-center text-gray">No generated letters.</td></tr>
                <?php else: foreach ($generatedLetters as $letter): ?>
                    <tr><td><?= e((string)$letter['title']) ?></td><td><?= e((string)$letter['letter_type']) ?></td><td><?= e((string)$letter['created_at']) ?></td><td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('employee/letterView/' . (string)$letter['id'])) ?>">View/Print</a></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Recent Payroll</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Run Date</th>
                                <th class="text-end">Net</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payrollHistory)): ?>
                                <tr><td colspan="5" class="text-center text-gray">No payroll generated yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payrollHistory as $row): ?>
                                    <?php
                                        $net = (float) ($row['net_pay'] ?? 0);
                                        $paid = (float) ($row['employee_paid_amount'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?= e((string) ($row['pay_period'] ?? '')) ?></td>
                                        <td><?= !empty($row['run_date']) ? e(format_date((string) $row['run_date'])) : '-' ?></td>
                                        <td class="text-end"><?= e(format_currency($net)) ?></td>
                                        <td class="text-end text-success"><?= e(format_currency($paid)) ?></td>
                                        <td class="text-end text-danger"><?= e(format_currency(max(0, $net - $paid))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Leave Balances</h5>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th class="text-end">Entitled</th>
                                <th class="text-end">Used</th>
                                <th class="text-end">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leave['items'])): ?>
                                <tr><td colspan="4" class="text-center text-gray">No leave balances for this year.</td></tr>
                            <?php else: ?>
                                <?php foreach ($leave['items'] as $item): ?>
                                    <tr>
                                        <td><?= e((string) ($item['leave_type_name'] ?? '')) ?></td>
                                        <td class="text-end"><?= e(number_format((float) ($item['entitled_days'] ?? 0), 1)) ?></td>
                                        <td class="text-end"><?= e(number_format((float) ($item['used_days'] ?? 0), 1)) ?></td>
                                        <td class="text-end fw-bold"><?= e(number_format((float) ($item['balance_days'] ?? 0), 1)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Attendance Payroll Rules</h2>
        <p class="text-gray mb-0">Configure how attendance affects payroll. Fixed salary clients stay unaffected while attendance impact is off.</p>
    </div>
    <form method="post" action="<?= e(base_url('attendance-payroll/create')) ?>">
        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Rule Set</button>
    </form>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<?php
$activeRule = $activeRule ?? [];
$impactOn = !empty($activeRule['attendance_impact_enabled']) && (string)($activeRule['payroll_mode'] ?? '') !== 'Fixed Salary Only';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="ent-stat-card" style="--ent-stat-accent:<?= $impactOn ? '#16a34a' : '#64748b' ?>">
            <span class="stat-label">Current Mode</span>
            <div class="stat-value" style="font-size:1.05rem"><?= e((string) ($activeRule['payroll_mode'] ?? 'Fixed Salary Only')) ?></div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px"><?= $impactOn ? 'Attendance can affect payroll' : 'Attendance does not affect payroll' ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="ent-stat-card" style="--ent-stat-accent:#2563eb">
            <span class="stat-label">Standard Day</span>
            <div class="stat-value" style="font-size:1.15rem"><?= e(number_format((float)($activeRule['standard_hours_per_day'] ?? 8), 2)) ?> hrs</div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Used for hourly deductions and overtime</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="ent-stat-card" style="--ent-stat-accent:#f97316">
            <span class="stat-label">Grace Period</span>
            <div class="stat-value" style="font-size:1.15rem"><?= e((string) ((int)($activeRule['grace_minutes'] ?? 15))) ?> mins</div>
            <div style="font-size:.74rem;color:var(--ent-text-muted);margin-top:4px">Late coming threshold</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">Rule Sets</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Mode</th>
                        <th>Effective</th>
                        <th>Rules Enabled</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="6" class="text-center text-gray">No rule sets found.</td></tr>
                <?php else: foreach ($rules as $rule): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string) $rule['name']) ?></div>
                            <div class="text-gray small"><?= e((string) ($rule['notes'] ?? '')) ?></div>
                        </td>
                        <td><?= e((string) $rule['payroll_mode']) ?></td>
                        <td>
                            <?= e((string) $rule['effective_from']) ?>
                            <?= !empty($rule['effective_to']) ? ' to ' . e((string) $rule['effective_to']) : '' ?>
                        </td>
                        <td>
                            <?php foreach ([
                                'attendance_impact_enabled' => 'Payroll Impact',
                                'late_deduction_enabled' => 'Late',
                                'absence_deduction_enabled' => 'Absence',
                                'overtime_enabled' => 'Overtime',
                                'night_shift_allowance_enabled' => 'Night Shift',
                            ] as $key => $label): ?>
                                <?php if (!empty($rule[$key])): ?><span class="badge bg-light text-dark border me-1"><?= e($label) ?></span><?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                        <td><span class="badge bg-<?= !empty($rule['is_active']) ? 'success' : 'secondary' ?>"><?= !empty($rule['is_active']) ? 'Active' : 'Inactive' ?></span></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('attendance-payroll/edit/' . (string) $rule['id'])) ?>" class="btn btn-sm btn-outline-primary">Configure</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

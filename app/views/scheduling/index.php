<?php
$fmtTime = static fn($time): string => $time ? substr((string) $time, 0, 5) : '-';
$activeShiftCount = count(array_filter($shifts ?? [], static fn($s): bool => (int)($s['is_active'] ?? 0) === 1));
$activeAssignmentCount = count(array_filter($assignments ?? [], static fn($a): bool => (int)($a['is_active'] ?? 0) === 1));
?>
<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Scheduling</h2>
        <p class="text-gray mb-0">Define expected shifts, weekly work patterns, and employee schedule assignments.</p>
    </div>
    <a href="<?= e(base_url('attendance/index')) ?>" class="btn btn-outline-secondary">View Attendance</a>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string) $flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string) $flashError) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Active Shifts</span><div class="stat-value"><?= $activeShiftCount ?></div><div class="small text-muted">Reusable work times</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Work Patterns</span><div class="stat-value"><?= count($patterns ?? []) ?></div><div class="small text-muted">Weekly roster templates</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Assigned Employees</span><div class="stat-value"><?= $activeAssignmentCount ?></div><div class="small text-muted">Active schedule assignments</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="ent-stat-card"><span class="stat-label">Roster Preview</span><div class="stat-value"><?= count($roster ?? []) ?></div><div class="small text-muted"><?= e((string)($month ?? date('Y-m'))) ?> scheduled rows</div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs ent-tabs mb-4" id="scheduleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="shifts-tab" data-bs-toggle="tab" data-bs-target="#shifts-pane" type="button" role="tab" aria-controls="shifts-pane" aria-selected="true">
                    <i class="bi bi-clock me-1"></i> Shifts
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="patterns-tab" data-bs-toggle="tab" data-bs-target="#patterns-pane" type="button" role="tab" aria-controls="patterns-pane" aria-selected="false">
                    <i class="bi bi-calendar-week me-1"></i> Work Patterns
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments-pane" type="button" role="tab" aria-controls="assignments-pane" aria-selected="false">
                    <i class="bi bi-person-check me-1"></i> Assignments
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="roster-tab" data-bs-toggle="tab" data-bs-target="#roster-pane" type="button" role="tab" aria-controls="roster-pane" aria-selected="false">
                    <i class="bi bi-calendar3-range me-1"></i> Roster Preview
                </button>
            </li>
        </ul>

        <div class="tab-content" id="scheduleTabsContent">
            <div class="tab-pane fade show active" id="shifts-pane" role="tabpanel" aria-labelledby="shifts-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <h5 class="mb-3">Create Shift</h5>
                        <form method="post" action="<?= e(base_url('scheduling/storeShift')) ?>" class="row g-3">
                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                            <div class="col-md-8"><label class="form-label">Shift Name</label><input class="form-control" name="name" placeholder="Day Shift" required></div>
                            <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" placeholder="DAY" required></div>
                            <div class="col-md-6"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" value="08:00" required></div>
                            <div class="col-md-6"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" value="17:00" required></div>
                            <div class="col-md-4"><label class="form-label">Break Minutes</label><input type="number" min="0" class="form-control" name="break_minutes" value="60"></div>
                            <div class="col-md-4"><label class="form-label">Grace Minutes</label><input type="number" min="0" class="form-control" name="grace_minutes" value="10"></div>
                            <div class="col-md-4"><label class="form-label">OT After Hours</label><input type="number" step="0.25" min="0" class="form-control" name="overtime_after_hours" placeholder="8"></div>
                            <div class="col-md-6"><label class="form-label">Color</label><input type="color" class="form-control form-control-color" name="color" value="#2563eb"></div>
                            <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="shiftActive" checked><label class="form-check-label" for="shiftActive">Active</label></div></div>
                            <div class="col-12"><button class="btn btn-primary w-100">Save Shift</button></div>
                        </form>
                    </div>

                    <div class="col-xl-8 table-responsive">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Shift Library</h5>
                            <span class="badge bg-light text-dark"><?= count($shifts ?? []) ?> shift(s)</span>
                        </div>
                        <table class="table table-striped align-middle">
                            <thead><tr><th>Shift</th><th>Time</th><th>Expected</th><th>Grace</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($shifts)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No shifts created yet.</td></tr>
                            <?php else: foreach ($shifts as $shift): ?>
                                <tr>
                                    <td><span class="badge" style="background:<?= e((string)($shift['color'] ?? '#64748b')) ?>">&nbsp;</span> <strong><?= e((string)$shift['name']) ?></strong><div class="small text-muted"><?= e((string)$shift['code']) ?></div></td>
                                    <td><?= e($fmtTime($shift['start_time'])) ?> - <?= e($fmtTime($shift['end_time'])) ?><div class="small text-muted"><?= (int)$shift['break_minutes'] ?> min break</div></td>
                                    <td><?= e(number_format((float)$shift['expected_hours'], 2)) ?> hrs</td>
                                    <td><?= (int)$shift['grace_minutes'] ?> min</td>
                                    <td class="text-end">
                                        <a href="<?= e(base_url('scheduling/editShift/' . (string)$shift['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="post" action="<?= e(base_url('scheduling/deleteShift/' . (string)$shift['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                            <button class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Archive this shift?">Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="patterns-pane" role="tabpanel" aria-labelledby="patterns-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-xl-5">
                        <h5 class="mb-3">Create Weekly Work Pattern</h5>
                        <form method="post" action="<?= e(base_url('scheduling/storePattern')) ?>" class="row g-3">
                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                            <div class="col-md-8"><label class="form-label">Pattern Name</label><input class="form-control" name="name" placeholder="Monday to Friday" required></div>
                            <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" placeholder="MF" required></div>
                            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" placeholder="Standard office working pattern"></textarea></div>
                            <?php foreach (($days ?? []) as $dayNumber => $dayName): ?>
                                <div class="col-md-6">
                                    <label class="form-label"><?= e((string)$dayName) ?></label>
                                    <select name="day_<?= (int)$dayNumber ?>_shift_id" class="form-select">
                                        <option value="0">Rest day</option>
                                        <?php foreach (($activeShifts ?? []) as $shift): ?>
                                            <option value="<?= (int)$shift['id'] ?>"><?= e((string)$shift['name']) ?> (<?= e($fmtTime($shift['start_time'])) ?>-<?= e($fmtTime($shift['end_time'])) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="patternActive" checked><label class="form-check-label" for="patternActive">Active pattern</label></div></div>
                            <div class="col-12"><button class="btn btn-primary w-100">Save Work Pattern</button></div>
                        </form>
                    </div>

                    <div class="col-xl-7 table-responsive">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Work Patterns</h5>
                            <span class="badge bg-light text-dark"><?= count($patterns ?? []) ?> pattern(s)</span>
                        </div>
                        <table class="table table-striped align-middle">
                            <thead><tr><th>Pattern</th><th>Working Days</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($patterns)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No work patterns created yet.</td></tr>
                            <?php else: foreach ($patterns as $pattern): ?>
                                <tr>
                                    <td><strong><?= e((string)$pattern['name']) ?></strong><div class="small text-muted"><?= e((string)$pattern['code']) ?></div></td>
                                    <td><?= (int)($pattern['working_days'] ?? 0) ?> of 7</td>
                                    <td><span class="badge bg-<?= (int)$pattern['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int)$pattern['is_active'] === 1 ? 'Active' : 'Archived' ?></span></td>
                                    <td class="text-end">
                                        <a href="<?= e(base_url('scheduling/editPattern/' . (string)$pattern['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="post" action="<?= e(base_url('scheduling/deletePattern/' . (string)$pattern['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                            <button class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Archive this work pattern?">Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="assignments-pane" role="tabpanel" aria-labelledby="assignments-tab" tabindex="0">
                <div class="row g-4">
                    <div class="col-xl-4">
                        <h5 class="mb-3">Assign Schedule</h5>
                        <form method="post" action="<?= e(base_url('scheduling/assign')) ?>" class="row g-3">
                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                            <div class="col-12">
                                <label class="form-label">Employee</label>
                                <select name="employee_id" class="form-select" required>
                                    <option value="">Select employee</option>
                                    <?php foreach (($employees ?? []) as $employee): ?>
                                        <option value="<?= (int)$employee['id'] ?>"><?= e((string)$employee['employee_number']) ?> - <?= e((string)$employee['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Work Pattern</label>
                                <select name="pattern_id" class="form-select" required>
                                    <option value="">Select work pattern</option>
                                    <?php foreach (($patterns ?? []) as $pattern): if ((int)$pattern['is_active'] !== 1) continue; ?>
                                        <option value="<?= (int)$pattern['id'] ?>"><?= e((string)$pattern['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6"><label class="form-label">Effective From</label><input type="date" class="form-control" name="effective_from" value="<?= e(date('Y-m-d')) ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Effective To</label><input type="date" class="form-control" name="effective_to"></div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control" placeholder="Temporary assignment, rotation, branch coverage, etc."></textarea></div>
                            <div class="col-12"><button class="btn btn-primary w-100">Assign Schedule</button></div>
                        </form>
                    </div>

                    <div class="col-xl-8 table-responsive">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0">Current Assignments</h5>
                            <span class="badge bg-light text-dark"><?= count($assignments ?? []) ?> assignment(s)</span>
                        </div>
                        <table class="table table-striped align-middle">
                            <thead><tr><th>Employee</th><th>Pattern</th><th>Period</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                            <tbody>
                            <?php if (empty($assignments)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No schedule assignments yet.</td></tr>
                            <?php else: foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><strong><?= e((string)$assignment['full_name']) ?></strong><div class="small text-muted"><?= e((string)$assignment['employee_number']) ?></div></td>
                                    <td><?= e((string)$assignment['pattern_name']) ?></td>
                                    <td><?= e((string)$assignment['effective_from']) ?> to <?= e((string)($assignment['effective_to'] ?: 'Open-ended')) ?></td>
                                    <td><span class="badge bg-<?= (int)$assignment['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int)$assignment['is_active'] === 1 ? 'Active' : 'Archived' ?></span></td>
                                    <td class="text-end">
                                        <form method="post" action="<?= e(base_url('scheduling/deleteAssignment/' . (string)$assignment['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                            <button class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Archive this assignment?">Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="roster-pane" role="tabpanel" aria-labelledby="roster-tab" tabindex="0">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="mb-1">Roster Preview</h5>
                        <p class="text-muted mb-0 small">Shows the first scheduled rows for the selected month.</p>
                    </div>
                    <form method="get" action="<?= e(base_url('scheduling/index')) ?>" class="d-flex gap-2 align-items-end">
                        <div><label class="form-label">Month</label><input type="month" name="month" value="<?= e((string)$month) ?>" class="form-control"></div>
                        <input type="hidden" name="tab" value="roster">
                        <button class="btn btn-outline-primary">View</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead><tr><th>Date</th><th>Employee</th><th>Pattern</th><th>Shift</th><th>Expected</th></tr></thead>
                        <tbody>
                        <?php if (empty($roster)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No roster rows found for this month.</td></tr>
                        <?php else: foreach ($roster as $row): $schedule = $row['schedule']; $employee = $row['employee']; ?>
                            <tr>
                                <td><?= e((string)$row['date']) ?></td>
                                <td><?= e((string)$employee['employee_number']) ?> - <?= e((string)$employee['full_name']) ?></td>
                                <td><?= e((string)($schedule['pattern_name'] ?? '-')) ?></td>
                                <td><?= e((string)($schedule['shift_name'] ?? '-')) ?> <span class="text-muted small"><?= e($fmtTime($schedule['start_time'] ?? null)) ?>-<?= e($fmtTime($schedule['end_time'] ?? null)) ?></span></td>
                                <td><?= e(number_format((float)($schedule['expected_hours'] ?? 0), 2)) ?> hrs</td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.js-confirm').forEach(function(button) {
    button.addEventListener('click', function(event) {
        if (!confirm(button.getAttribute('data-confirm') || 'Are you sure?')) {
            event.preventDefault();
        }
    });
});

(function() {
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab') || window.location.hash.replace('#', '');
    if (tab) {
        var trigger = document.querySelector('[data-bs-target="#' + tab + '-pane"]');
        if (trigger && window.bootstrap) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }
    document.querySelectorAll('#scheduleTabs [data-bs-toggle="tab"]').forEach(function(trigger) {
        trigger.addEventListener('shown.bs.tab', function(event) {
            var pane = event.target.getAttribute('data-bs-target') || '';
            var name = pane.replace('#', '').replace('-pane', '');
            if (name) {
                history.replaceState(null, '', window.location.pathname + window.location.search.replace(/([?&])tab=[^&]*&?/, '$1').replace(/[?&]$/, '') + '#' + name);
            }
        });
    });
})();
</script>

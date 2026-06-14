<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">My Leave</h2>
        <p class="text-gray mb-0">Balances and requests for <?= date('Y') ?>.</p>
    </div>
    <?php if (!empty($leaveTypes)): ?>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#applyLeaveForm" aria-expanded="false">
        <i class="bi bi-plus-lg me-1"></i> Apply for Leave
    </button>
    <?php endif; ?>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<!-- Apply for Leave form (collapsed by default) -->
<?php if (!empty($leaveTypes)): ?>
<div class="collapse mb-4" id="applyLeaveForm">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3"><i class="bi bi-calendar-plus me-2 text-primary"></i>New Leave Request</h5>
            <form method="post" action="<?= e(base_url('portal/leaveApply')) ?>" class="row g-3">
                <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? Session::csrfToken())) ?>">

                <div class="col-md-4">
                    <label class="form-label">Leave Type *</label>
                    <select name="leave_type_id" class="form-select" required id="leaveTypeSelect">
                        <option value="">— Select —</option>
                        <?php foreach ($leaveTypes as $lt):
                            $bal = $balances[(int)$lt['id']] ?? ['entitled_days'=>(float)$lt['days_per_year'],'used_days'=>0];
                            $rem = max(0.0, (float)$bal['entitled_days'] - (float)$bal['used_days']);
                        ?>
                        <option value="<?= (int)$lt['id'] ?>" data-remaining="<?= $rem ?>">
                            <?= e((string)$lt['name']) ?> (<?= number_format($rem, 1) ?> days left)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Start Date *</label>
                    <input type="date" name="start_date" class="form-control" required
                           min="<?= date('Y-m-d') ?>" id="leaveStart">
                </div>

                <div class="col-md-3">
                    <label class="form-label">End Date *</label>
                    <input type="date" name="end_date" class="form-control" required
                           min="<?= date('Y-m-d') ?>" id="leaveEnd">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <div class="w-100 text-center p-2 rounded" style="background:#f1f5f9">
                        <div style="font-size:.7rem;color:#64748b">Days</div>
                        <div class="fw-bold fs-5" id="leaveDayCount">—</div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Reason <span class="text-muted">(optional)</span></label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Brief reason for leave…"></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Request</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#applyLeaveForm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Balance cards -->
<div class="row g-3 mb-4">
    <?php foreach ($leaveTypes as $lt):
        $bal      = $balances[(int)$lt['id']] ?? ['entitled_days'=>(float)$lt['days_per_year'],'used_days'=>0.0];
        $entitled = (float)$bal['entitled_days'];
        $used     = (float)$bal['used_days'];
        $rem      = max(0.0, $entitled - $used);
        $pct      = $entitled > 0 ? min(100, round($used / $entitled * 100)) : 0;
        $accent   = $rem <= 0 ? '#dc2626' : ($pct >= 75 ? '#d97706' : '#16a34a');
    ?>
    <div class="col-6 col-md-3">
        <div class="ent-stat-card" style="--ent-stat-accent:<?= $accent ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label"><?= e((string)$lt['name']) ?></span>
                <span class="stat-icon" style="background:<?= $rem <= 0 ? '#fee2e2' : ($pct >= 75 ? '#fef3c7' : '#dcfce7') ?>;color:<?= $accent ?>"><i class="bi bi-calendar-heart"></i></span>
            </div>
            <div class="stat-value"><?= number_format($rem, 1) ?> <span style="font-size:.8rem;font-weight:400">days</span></div>
            <div style="font-size:.72rem;color:var(--ent-text-muted);margin:4px 0 6px">of <?= number_format($entitled, 0) ?> days used <?= number_format($used, 1) ?></div>
            <div class="progress" style="height:4px;border-radius:2px">
                <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $accent ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Requests table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold mb-3">My Leave Requests</h6>
        <?php if (empty($requests)): ?>
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No leave requests on record yet. Use the <strong>Apply for Leave</strong> button above to submit one.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th class="text-center">Days</th>
                            <th>Reason</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $req):
                        $sc = ['Pending'=>'warning text-dark','Approved'=>'success','Rejected'=>'danger','Cancelled'=>'secondary'][$req['status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><?= e((string)$req['leave_type_name']) ?></td>
                        <td><?= e((string)$req['start_date']) ?></td>
                        <td><?= e((string)$req['end_date']) ?></td>
                        <td class="text-center"><?= number_format((float)$req['total_days'], 1) ?></td>
                        <td class="text-muted" style="font-size:.82rem;max-width:180px"><?= e((string)($req['reason'] ?? '—')) ?></td>
                        <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$req['status']) ?></span></td>
                        <td class="text-end">
                            <?php if ($req['status'] === 'Pending'): ?>
                            <form method="post" action="<?= e(base_url('portal/leaveCancelPortal/' . (string)$req['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? Session::csrfToken())) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Cancel this leave request?')">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:.8rem">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function(){
    var start = document.getElementById('leaveStart');
    var end   = document.getElementById('leaveEnd');
    var count = document.getElementById('leaveDayCount');
    function calcDays(){
        if (!start || !end || !start.value || !end.value) { count.textContent = '—'; return; }
        var s = new Date(start.value), e = new Date(end.value);
        var d = Math.round((e - s) / 86400000) + 1;
        count.textContent = d >= 1 ? d : '—';
        count.style.color = d >= 1 ? '#1d4ed8' : '#dc2626';
    }
    if (start) start.addEventListener('change', function(){ if(end.value && end.value < start.value){ end.value = start.value; } calcDays(); });
    if (end)   end.addEventListener('change',   calcDays);
})();
</script>

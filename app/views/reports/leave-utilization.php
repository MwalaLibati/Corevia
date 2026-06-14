<?php
$exportQs = http_build_query(['year' => $year, 'dept_id' => $deptId, 'export' => 'csv']);

$totals = [];
foreach ($leaveTypes as $lt) {
    $tid = (int)$lt['id'];
    $totals[$tid] = ['entitled' => 0.0, 'used' => 0.0, 'remaining' => 0.0, 'pending' => 0.0];
}
foreach ($matrix as $row) {
    foreach ($leaveTypes as $lt) {
        $tid = (int)$lt['id'];
        $t   = $row['types'][$tid];
        $totals[$tid]['entitled']  += $t['entitled'];
        $totals[$tid]['used']      += $t['used'];
        $totals[$tid]['remaining'] += $t['remaining'];
        $totals[$tid]['pending']   += $t['pending'];
    }
}
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h2 class="text-dark">Leave Utilization</h2>
        <p class="text-muted mb-0">Reads from <code>leave_balances</code> — the same source as the portal &amp; leave approval workflow.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="get" action="<?= e(base_url('report/leaveUtilization')) ?>" class="d-flex gap-2">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:90px">
                <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y ?>" <?= (int)$y === $year ? 'selected' : '' ?>><?= (int)$y ?></option>
                <?php endforeach; ?>
            </select>
            <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width:160px">
                <option value="0">All Departments</option>
                <?php foreach ($departments as $dep): ?>
                <option value="<?= (int)$dep['id'] ?>" <?= (int)$dep['id'] === $deptId ? 'selected' : '' ?>><?= e((string)$dep['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="<?= e(base_url('report/leaveUtilization?' . $exportQs)) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i>CSV
        </a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-sm btn-outline-primary">← Reports</a>
    </div>
</div>

<!-- Legend -->
<div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:.76rem">
    <span><span style="display:inline-block;width:10px;height:10px;background:#dcfce7;border:1px solid #16a34a;border-radius:2px;margin-right:4px"></span>≤ 60% used (healthy)</span>
    <span><span style="display:inline-block;width:10px;height:10px;background:#fef3c7;border:1px solid #d97706;border-radius:2px;margin-right:4px"></span>60–89% used (caution)</span>
    <span><span style="display:inline-block;width:10px;height:10px;background:#fee2e2;border:1px solid #dc2626;border-radius:2px;margin-right:4px"></span>≥ 90% used or overdrawn</span>
    <span><span style="display:inline-block;width:10px;height:10px;background:#ede9fe;border:1px solid #7c3aed;border-radius:2px;margin-right:4px"></span>Pending approval</span>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" style="font-size:.78rem">
                <thead>
                    <!-- Row 1: leave type group headers -->
                    <tr class="table-dark">
                        <th rowspan="2" style="min-width:160px;vertical-align:middle">Employee</th>
                        <th rowspan="2" style="min-width:110px;vertical-align:middle">Department</th>
                        <?php foreach ($leaveTypes as $lt): ?>
                        <th colspan="3" class="text-center" style="min-width:140px"><?= e((string)$lt['name']) ?>
                            <div style="font-size:.68rem;font-weight:400;opacity:.8"><?= (int)$lt['days_per_year'] ?> days/yr default</div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Row 2: sub-column headers -->
                    <tr class="table-secondary">
                        <?php foreach ($leaveTypes as $lt): ?>
                        <th class="text-center" style="font-size:.7rem">Used</th>
                        <th class="text-center" style="font-size:.7rem">Remaining</th>
                        <th class="text-center" style="font-size:.7rem">Pending</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($matrix)): ?>
                    <tr><td colspan="<?= 2 + count($leaveTypes) * 3 ?>" class="text-center text-muted py-5">No active employees found.</td></tr>
                <?php else: foreach ($matrix as $row): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$row['employee_name']) ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= e((string)$row['employee_number']) ?></div>
                    </td>
                    <td class="text-muted"><?= e((string)$row['department_name']) ?></td>
                    <?php foreach ($leaveTypes as $lt):
                        $tid = (int)$lt['id'];
                        $t   = $row['types'][$tid];
                        $ent = $t['entitled'];
                        $usd = $t['used'];
                        $rem = $t['remaining'];
                        $pnd = $t['pending'];
                        $pct = $ent > 0 ? min(100, $usd / $ent * 100) : 0;
                        if ($rem < 0 || $pct >= 90)      { $bg = '#fee2e2'; $tc = '#991b1b'; }
                        elseif ($pct >= 60)               { $bg = '#fef3c7'; $tc = '#92400e'; }
                        else                              { $bg = '#f0fdf4'; $tc = '#14532d'; }
                    ?>
                    <td class="text-center" style="background:<?= $bg ?>;color:<?= $tc ?>;font-weight:600">
                        <?= $usd > 0 ? number_format($usd, 1) : '<span style="opacity:.4">0</span>' ?>
                        <div class="progress mt-1" style="height:3px;border-radius:2px;background:rgba(0,0,0,.08)">
                            <div style="width:<?= min(100, round($pct)) ?>%;height:3px;background:<?= $tc ?>;border-radius:2px"></div>
                        </div>
                    </td>
                    <td class="text-center" style="<?= $rem < 0 ? 'color:#dc2626;font-weight:700' : '' ?>">
                        <?= number_format($rem, 1) ?>
                    </td>
                    <td class="text-center" style="<?= $pnd > 0 ? 'background:#ede9fe;color:#5b21b6;font-weight:600' : 'color:#94a3b8' ?>">
                        <?= $pnd > 0 ? number_format($pnd, 1) : '—' ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
                <?php if (!empty($matrix)): ?>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="2" class="text-end text-muted" style="font-size:.76rem">TOTALS</td>
                        <?php foreach ($leaveTypes as $lt):
                            $tid = (int)$lt['id'];
                            $t   = $totals[$tid];
                        ?>
                        <td class="text-center"><?= number_format($t['used'], 1) ?></td>
                        <td class="text-center <?= $t['remaining'] < 0 ? 'text-danger' : '' ?>"><?= number_format($t['remaining'], 1) ?></td>
                        <td class="text-center <?= $t['pending'] > 0 ? 'text-purple' : '' ?>"><?= $t['pending'] > 0 ? number_format($t['pending'],1) : '—' ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="text-muted mt-2" style="font-size:.74rem">
    <i class="bi bi-info-circle me-1"></i>
    Entitled days reflect per-employee adjustments in <code>leave_balances</code> where set; otherwise defaults to the leave type's standard <code>days_per_year</code>.
    Pending days are not deducted from the remaining balance until approved.
</div>

<?php
$e = $employee;
$initial = mb_strtoupper(mb_substr((string)($e['full_name'] ?? ''), 0, 1));
$completion = $profileCompletion ?? ['percent' => 0, 'missing' => []];
function pfield_ro(string $label, mixed $value, string $icon = ''): void {
    $v = trim((string)($value ?? ''));
    echo '<div class="col-md-6 col-lg-4 mb-3">';
    echo '<div class="text-uppercase fw-semibold mb-1" style="font-size:.68rem;color:#64748b;letter-spacing:.5px">';
    if ($icon) echo '<i class="bi ' . $icon . ' me-1"></i>';
    echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</div>';
    echo '<div style="font-size:.88rem;color:#111827;font-weight:500">' . ($v !== '' ? htmlspecialchars($v, ENT_QUOTES, 'UTF-8') : '<span style="color:#9ca3af">—</span>') . '</div>';
    echo '</div>';
}
?>

<?php if (!empty($flashError)):   ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-primary">Profile Completion</h6>
            <span class="badge bg-primary"><?= (int) ($completion['percent'] ?? 0) ?>%</span>
        </div>
        <div class="progress mb-2" style="height:6px">
            <div class="progress-bar" style="width:<?= (int) ($completion['percent'] ?? 0) ?>%"></div>
        </div>
        <?php if (!empty($completion['missing'])): ?>
            <div class="text-muted" style="font-size:.82rem">Missing: <?= e(implode(', ', (array) $completion['missing'])) ?></div>
        <?php else: ?>
            <div class="text-success" style="font-size:.82rem">Your profile is complete.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Identity card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-4">
            <div class="ent-header-avatar" style="width:56px;height:56px;font-size:1.4rem"><?= e($initial) ?></div>
            <div>
                <div class="fw-bold" style="font-size:1.1rem"><?= e((string)($e['full_name'] ?? '')) ?></div>
                <div class="text-muted" style="font-size:.83rem"><?= e((string)($e['designation'] ?? '')) ?> &bull; <?= e((string)($e['employee_number'] ?? '')) ?></div>
            </div>
            <span class="badge bg-<?= (string)($e['contract_status'] ?? '') === 'Active' ? 'success' : 'secondary' ?> ms-auto">
                <?= e((string)($e['contract_status'] ?? 'N/A')) ?>
            </span>
        </div>

        <!-- Read-only employment info -->
        <h6 class="fw-bold mb-3 text-primary">Employment Details</h6>
        <div class="row">
            <?php
            pfield_ro('Employee No.',    $e['employee_number'],       'bi-hash');
            pfield_ro('Department',      $e['department_name'] ?? '', 'bi-building');
            pfield_ro('Designation',     $e['designation']    ?? '', 'bi-briefcase');
            pfield_ro('Employment Type', $e['employment_type'] ?? '', 'bi-person-badge');
            pfield_ro('Join Date',       $e['hired_at']       ?? '', 'bi-calendar-plus');
            pfield_ro('Email',           $e['email']          ?? '', 'bi-envelope');
            ?>
        </div>
    </div>
</div>

<?php if (!empty($profileRequests)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3 text-primary">Profile Change Requests</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Changes</th>
                        <th>Reviewed</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($profileRequests as $request): ?>
                    <?php
                    $changes = json_decode((string) ($request['requested_changes_json'] ?? '{}'), true);
                    $changes = is_array($changes) ? $changes : [];
                    $labels = array_map(static fn(string $field): string => ucwords(str_replace('_', ' ', $field)), array_keys($changes));
                    $status = (string) ($request['status'] ?? 'Pending');
                    $badge = $status === 'Approved' ? 'success' : ($status === 'Rejected' ? 'danger' : 'warning text-dark');
                    ?>
                    <tr>
                        <td><?= e(!empty($request['created_at']) ? date('d M Y', strtotime((string) $request['created_at'])) : '-') ?></td>
                        <td><span class="badge bg-<?= e($badge) ?>"><?= e($status) ?></span></td>
                        <td><?= e(implode(', ', $labels)) ?></td>
                        <td class="text-muted"><?= e(!empty($request['reviewed_at']) ? date('d M Y', strtotime((string) $request['reviewed_at'])) : '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Editable personal details -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h6 class="fw-bold mb-0 text-primary">Personal Details</h6>
                <div class="text-muted small">Changes are submitted to HR for approval before your official record is updated.</div>
            </div>
            <span class="badge bg-light text-primary border"><i class="bi bi-send me-1"></i>Request update</span>
        </div>
        <form method="post" action="<?= e(base_url('portal/profileUpdate')) ?>" class="row g-3">
            <input type="hidden" name="_csrf" value="<?= e((string)($csrf ?? Session::csrfToken())) ?>">

            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control"
                       value="<?= e((string)($e['phone'] ?? '')) ?>"
                       placeholder="+260 97X XXX XXX">
            </div>

            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender_id" class="form-select">
                    <option value="">— Select —</option>
                    <?php foreach ($genders as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= (int)($e['gender_id'] ?? 0) === (int)$g['id'] ? 'selected' : '' ?>>
                        <?= e((string)$g['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control"
                       value="<?= e((string)($e['date_of_birth'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">NRC Number</label>
                <input type="text" name="nrc_number" class="form-control"
                       value="<?= e((string)($e['nrc_number'] ?? '')) ?>"
                       placeholder="000000/00/0">
            </div>

            <div class="col-md-4">
                <label class="form-label">NAPSA Number</label>
                <input type="text" name="napsa_number" class="form-control"
                       value="<?= e((string)($e['napsa_number'] ?? '')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">TPIN</label>
                <input type="text" name="tpin" class="form-control"
                       value="<?= e((string)($e['tpin'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Name</label>
                <input type="text" name="bank_name" class="form-control"
                       value="<?= e((string)($e['bank_name'] ?? '')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Bank Account Number</label>
                <input type="text" name="bank_account_number" class="form-control"
                       value="<?= e((string)($e['bank_account_number'] ?? '')) ?>">
            </div>

            <div class="col-12">
                <label class="form-label">Home Address</label>
                <textarea name="address" class="form-control" rows="2"
                          placeholder="Street / Area, City"><?= e((string)($e['address'] ?? '')) ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Change Request</button>
            </div>
        </form>
    </div>
</div>

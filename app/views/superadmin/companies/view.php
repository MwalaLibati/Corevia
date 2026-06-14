<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0"><?= e((string)$company['name']) ?></h2>
        <p class="text-muted mb-0 mt-1"><code><?= e((string)$company['slug']) ?></code>.<?= e(app_platform_domain()) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('superadmin/company/edit/'.$company['id'])) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <form method="post" action="<?= e(base_url('superadmin/company/toggle/'.$company['id'])) ?>" class="d-inline" onsubmit="return confirm('<?= $company['is_active'] ? 'Deactivate' : 'Activate' ?> this company?')">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <button type="submit" class="btn btn-sm <?= $company['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                <i class="bi bi-<?= $company['is_active'] ? 'pause-circle' : 'play-circle' ?> me-1"></i>
                <?= $company['is_active'] ? 'Deactivate' : 'Activate' ?>
            </button>
        </form>
        <a href="<?= e(base_url('superadmin/company/index')) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
    </div>
</div>

<div class="card border-danger shadow-sm mb-4">
    <div class="card-body">
        <h6 class="text-danger fw-bold mb-2">Delete Company</h6>
        <p class="text-muted mb-3" style="font-size:.86rem">
            This removes the company from active platform screens, disables company access, and cancels active subscriptions. Historical data is retained for recovery and audit.
        </p>
        <form method="post" action="<?= e(base_url('superadmin/company/delete/' . (string)$company['id'])) ?>" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Type company name to confirm</label>
                <input type="text" name="confirm_name" class="form-control" required placeholder="<?= e((string)$company['name']) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold">Reason</label>
                <input type="text" name="deletion_reason" class="form-control" value="Deleted by platform admin">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Delete this company from active platform records?')">Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $vstats = [
        ['label'=>'Employees',    'val'=>$employees,   'icon'=>'bi-people-fill',   'bg'=>'#dbeafe','ic'=>'#1d4ed8'],
        ['label'=>'Branches',     'val'=>$branches ?? 0, 'icon'=>'bi-geo-alt-fill', 'bg'=>'#cffafe','ic'=>'#0891b2'],
        ['label'=>'Admin Users',  'val'=>$users,       'icon'=>'bi-person-badge',  'bg'=>'#ede9fe','ic'=>'#7c3aed'],
        ['label'=>'Payroll Runs', 'val'=>$payrollRuns, 'icon'=>'bi-receipt',       'bg'=>'#dcfce7','ic'=>'#16a34a'],
        ['label'=>'Status',       'val'=>$company['is_active'] ? 'Active' : 'Inactive', 'icon'=>'bi-check-circle', 'bg'=>$company['is_active']?'#dcfce7':'#fee2e2','ic'=>$company['is_active']?'#16a34a':'#dc2626'],
    ];
    foreach ($vstats as $s): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span style="font-size:.72rem;color:#64748b;font-weight:600"><?= $s['label'] ?></span>
                    <span style="width:28px;height:28px;background:<?= $s['bg'] ?>;color:<?= $s['ic'] ?>;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.9rem"><i class="bi <?= $s['icon'] ?>"></i></span>
                </div>
                <div style="font-size:1.4rem;font-weight:700;color:#0f172a"><?= is_int($s['val']) ? $s['val'] : e((string)$s['val']) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Company Info -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Company Details</h6>
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Name</td><td class="fw-semibold"><?= e((string)$company['name']) ?></td></tr>
                    <tr><td class="text-muted">Entity</td><td><?= e((string)($company['client_entity_name'] ?? 'Single Company')) ?></td></tr>
                    <tr><td class="text-muted">Slug</td><td><code><?= e((string)$company['slug']) ?></code></td></tr>
                    <tr><td class="text-muted">Email</td><td><?= e((string)($company['email'] ?? '—')) ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td><?= e((string)($company['phone'] ?? '—')) ?></td></tr>
                    <tr><td class="text-muted">Plan</td><td><span class="badge bg-secondary"><?= e((string)$company['subscription_plan']) ?></span></td></tr>
                    <tr><td class="text-muted">Registered</td><td><?= e(date('d M Y', strtotime((string)$company['created_at']))) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscriptions -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Subscription History</h6>
                <?php if (empty($subscriptions)): ?>
                    <p class="text-muted text-center py-4 mb-0">No subscription records.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:.8rem">
                        <thead><tr><th>Plan</th><th>Cycle</th><th>Starts</th><th>Ends</th><th class="text-center">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($subscriptions as $s):
                            $sc = ['Active'=>'success','Expired'=>'danger','Cancelled'=>'secondary','Pending'=>'warning'][$s['status']] ?? 'secondary';
                        ?>
                        <tr>
                            <td><?= e((string)$s['plan']) ?></td>
                            <td><?= e((string)$s['billing_cycle']) ?></td>
                            <td><?= e((string)$s['starts_at']) ?></td>
                            <td><?= e((string)$s['ends_at']) ?></td>
                            <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$s['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


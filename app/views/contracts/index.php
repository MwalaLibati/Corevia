<?php
$statusColors = [
    'Active'     => 'success',
    'Expired'    => 'danger',
    'Terminated' => 'secondary',
    'Renewed'    => 'info',
];
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Contract Management</h2>
        <p class="text-gray mb-0">Track employee contracts and expiry dates.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(base_url('settings/email')) ?>" class="btn btn-outline-secondary">&#9993; Email Settings</a>
        <form method="post" action="<?= e(base_url('contract/emailAllActive')) ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <button type="submit" class="btn btn-outline-success" onclick="return confirm('Email all active contracts to employee email addresses?');">&#9993; Email Active Contracts</button>
        </form>
        <form method="post" action="<?= e(base_url('contract/sendReminders')) ?>" class="d-inline">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
            <button type="submit" class="btn btn-outline-warning" onclick="return confirm('Send all pending contract reminder emails now?');">&#128276; Send Reminders</button>
        </form>
        <a href="<?= e(base_url('contract/create')) ?>" class="btn btn-primary">New Contract</a>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php if (!empty($expiring)): ?>
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-18 mt-1 flex-shrink-0"></i>
        <div>
            <strong><?= count($expiring) ?> contract(s) expiring within 30 days:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($expiring as $exp): ?>
                    <li>
                        <strong><?= e((string) $exp['employee_name']) ?></strong>
                        (<?= e((string) $exp['employee_number']) ?>) &mdash;
                        <?= e((string) $exp['contract_number']) ?>,
                        expires <strong><?= e((string) $exp['end_date']) ?></strong>
                        &nbsp;<a href="<?= e(base_url('contract/renew/' . (string) $exp['id'])) ?>" class="btn btn-sm btn-warning py-0 px-2">Renew</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($renewalRequests)): ?>
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
        <i class="bi bi-arrow-repeat fs-18 mt-1 flex-shrink-0"></i>
        <div class="w-100">
            <strong><?= count($renewalRequests) ?> pending contract renewal request(s)</strong>
            <div class="table-responsive mt-2">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Contract</th>
                            <th>Current End Date</th>
                            <th>Preferred New End Date</th>
                            <th>Reason</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renewalRequests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string)($request['employee_name'] ?? '')) ?></strong>
                                    <div class="small text-gray"><?= e((string)($request['employee_number'] ?? '')) ?></div>
                                </td>
                                <td><?= e((string)($request['contract_number'] ?? '-')) ?></td>
                                <td><?= !empty($request['end_date']) ? e((string)$request['end_date']) : '<span class="text-gray">No expiry</span>' ?></td>
                                <td><?= !empty($request['requested_end_date']) ? e((string)$request['requested_end_date']) : '<span class="text-gray">Not specified</span>' ?></td>
                                <td><?= trim((string)($request['reason'] ?? '')) !== '' ? e((string)$request['reason']) : '<span class="text-gray">No note</span>' ?></td>
                                <td class="text-end">
                                    <a href="<?= e(base_url('contract/renew/' . (string) $request['contract_id'])) ?>" class="btn btn-sm btn-success">Renew</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(base_url('contract/index')) ?>" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" value="<?= e((string) ($search ?? '')) ?>" placeholder="Name, employee number, or contract number">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="filter" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach (['Active', 'Expired', 'Terminated', 'Renewed'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= (($filter ?? '') === $s) ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="<?= e(base_url('contract/index')) ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Contract No.</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Template</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($contracts)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-gray">No contracts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contracts as $contract): ?>
                            <?php
                                $status   = (string) ($contract['status'] ?? 'Active');
                                $color    = $statusColors[$status] ?? 'secondary';
                                $endDate  = (string) ($contract['end_date'] ?? '');
                                $isExpiring = $status === 'Active' && $endDate !== '' &&
                                              $endDate >= date('Y-m-d') &&
                                              $endDate <= date('Y-m-d', strtotime('+30 days'));
                            ?>
                            <tr>
                                <td><span class="fw-semibold"><?= e((string) ($contract['contract_number'] ?? '-')) ?></span></td>
                                <td>
                                    <div><?= e((string) ($contract['employee_name'] ?? '')) ?></div>
                                    <small class="text-gray"><?= e((string) ($contract['employee_number'] ?? '')) ?></small>
                                </td>
                                <td><?= e((string) ($contract['department_name'] ?? '-')) ?></td>
                                <td><?= e((string) ($contract['contract_type'] ?? '-')) ?></td>
                                <td>
                                    <?php if (!empty($contract['template_name'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:.72rem">
                                            <?= e((string) $contract['template_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($contract['start_date'] ?? '-')) ?></td>
                                <td>
                                    <?php if ($endDate !== ''): ?>
                                        <span class="<?= $isExpiring ? 'text-warning fw-semibold' : '' ?>">
                                            <?= e($endDate) ?>
                                            <?= $isExpiring ? '<i class="bi bi-clock-history ms-1"></i>' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray">No expiry</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $color ?>-subtle text-<?= $color ?>-emphasis"><?= e($status) ?></span>
                                    <?php if (($contract['approval_status'] ?? 'Approved') !== 'Approved'): ?>
                                        <div class="small mt-1"><span class="badge bg-warning text-dark"><?= e((string)$contract['approval_status']) ?></span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(base_url('contract/download/' . (string) $contract['id'])) ?>" class="btn btn-sm btn-outline-secondary" title="Download Contract" target="_blank">&#8615; Download</a>
                                    <form method="post" action="<?= e(base_url('contract/email/' . (string) $contract['id'])) ?>" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Email this contract to <?= e((string) ($contract['employee_email'] ?? 'the employee')) ?>?');">&#9993; Email</button>
                                    </form>
                                    <a href="<?= e(base_url('contract/edit/' . (string) $contract['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <?php if (in_array($status, ['Active', 'Expired'], true)): ?>
                                        <a href="<?= e(base_url('contract/renew/' . (string) $contract['id'])) ?>" class="btn btn-sm btn-outline-success">Renew</a>
                                    <?php endif; ?>
                                    <?php if ($status === 'Active'): ?>
                                        <form method="post" action="<?= e(base_url('contract/terminate/' . (string) $contract['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Terminate this contract?');">Terminate</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array((string)($contract['approval_status'] ?? ''), ['Pending HR Review','Pending Admin Approval'], true)): ?>
                                        <form method="post" action="<?= e(base_url('contract/approve/' . (string) $contract['id'])) ?>" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-outline-success">Approve Step</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

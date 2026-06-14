<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark mb-0">Companies</h2>
        <p class="text-muted mb-0 mt-1">Manage all tenant companies on this platform.</p>
    </div>
    <a href="<?= e(base_url('superadmin/company/create')) ?>" class="btn btn-sm" style="background:#7c3aed;color:#fff">
        <i class="bi bi-plus-circle me-1"></i>New Company
    </a>
</div>

<script>
function confirmCompanyDelete(form, companyName) {
    var typed = window.prompt('Type the company name exactly to delete it:\n\n' + companyName);
    if (typed === null) {
        return false;
    }
    form.querySelector('input[name="confirm_name"]').value = typed;
    return typed === companyName;
}
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.82rem">
                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Entity / Group</th>
                        <th>Slug / URL</th>
                        <th>Email</th>
                        <th class="text-center">Branches</th>
                        <th class="text-center">Employees</th>
                        <th class="text-center">Users</th>
                        <th>Plan</th>
                        <th>Sub Until</th>
                        <th class="text-center">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($companies)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-5">No companies yet.</td></tr>
                <?php else: foreach ($companies as $c): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$c['name']) ?></div>
                        <div class="text-muted" style="font-size:.7rem"><?= e((string)($c['email'] ?? '')) ?></div>
                    </td>
                    <td><?= e((string)($c['client_entity_name'] ?? 'Single Company')) ?></td>
                    <td>
                        <code><?= e((string)$c['slug']) ?></code>
                        <div class="text-muted" style="font-size:.68rem"><?= e((string)$c['slug']) ?>.<?= e(app_platform_domain()) ?></div>
                    </td>
                    <td><?= e((string)($c['email'] ?? '-')) ?></td>
                    <td class="text-center"><?= (int)($c['branch_count'] ?? 0) ?></td>
                    <td class="text-center fw-semibold"><?= (int)$c['employee_count'] ?></td>
                    <td class="text-center"><?= (int)$c['user_count'] ?></td>
                    <td>
                        <?php
                        $planColors = ['Trial'=>'secondary','Basic'=>'info','Standard'=>'primary','Premium'=>'success'];
                        $plan = (string)($c['sub_plan'] ?? $c['subscription_plan']);
                        ?>
                        <span class="badge bg-<?= $planColors[$plan] ?? 'secondary' ?>"><?= e($plan) ?></span>
                    </td>
                    <td>
                        <?php if ($c['sub_ends_at']):
                            $daysLeft = (int) ceil((strtotime((string)$c['sub_ends_at']) - time()) / 86400);
                            $cls = $daysLeft < 30 ? 'text-danger fw-bold' : ($daysLeft < 60 ? 'text-warning' : '');
                        ?>
                        <span class="<?= $cls ?>"><?= e(date('d M Y', strtotime((string)$c['sub_ends_at']))) ?></span>
                        <?php if ($daysLeft < 60): ?><div style="font-size:.68rem;color:inherit"><?= $daysLeft ?> days left</div><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($c['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= e(base_url('superadmin/company/view/'.$c['id'])) ?>" class="btn btn-xs btn-outline-primary" style="font-size:.72rem;padding:2px 8px" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= e(base_url('superadmin/company/edit/'.$c['id'])) ?>" class="btn btn-xs btn-outline-secondary" style="font-size:.72rem;padding:2px 8px" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="<?= e(base_url('superadmin/company/toggle/'.$c['id'])) ?>" class="d-inline" onsubmit="return confirm('<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?> this company?')">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit"
                                        class="btn btn-xs <?= $c['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        style="font-size:.72rem;padding:2px 8px"
                                        title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="bi bi-<?= $c['is_active'] ? 'pause-circle' : 'play-circle' ?>"></i>
                                </button>
                            </form>
                            <form method="post"
                                  action="<?= e(base_url('superadmin/company/delete/'.$c['id'])) ?>"
                                  class="d-inline"
                                  onsubmit="return confirmCompanyDelete(this, '<?= e((string)$c['name']) ?>')">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <input type="hidden" name="confirm_name" value="">
                                <input type="hidden" name="deletion_reason" value="Deleted from company list.">
                                <button type="submit"
                                        class="btn btn-xs btn-outline-danger"
                                        style="font-size:.72rem;padding:2px 8px"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


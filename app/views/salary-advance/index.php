<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Salary Advances</h2>
        <p class="text-gray mb-0">Manage employee salary advances and repayment schedules.</p>
    </div>
    <?php if (in_array(current_user()['role'] ?? '', ['Super Admin','Finance Officer'], true)): ?>
        <a href="<?= e(base_url('salary-advance/create')) ?>" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> New Advance</a>
    <?php endif; ?>
</div>

<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>
<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Monthly Deduction</th>
                    <th class="text-end">Outstanding</th>
                    <th>Start Date</th>
                    <th class="text-center">Status</th>
                    <th>Approved By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($advances)): ?>
                <tr><td colspan="8" class="text-center text-gray">No salary advances found.</td></tr>
            <?php else: ?>
                <?php foreach ($advances as $adv):
                    $sc = ['Pending'=>'warning','Active'=>'success','Completed'=>'info','Cancelled'=>'secondary'][$adv['status']] ?? 'secondary';
                    $pct = (float)$adv['amount'] > 0 ? round(((float)$adv['amount'] - (float)$adv['outstanding_balance']) / (float)$adv['amount'] * 100) : 100;
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$adv['employee_name']) ?></div>
                        <div class="text-muted small"><?= e((string)$adv['employee_number']) ?></div>
                    </td>
                    <td class="text-end"><?= e(format_currency((float)$adv['amount'])) ?></td>
                    <td class="text-end"><?= e(format_currency((float)$adv['monthly_deduction'])) ?></td>
                    <td class="text-end">
                        <div><?= e(format_currency((float)$adv['outstanding_balance'])) ?></div>
                        <div class="progress mt-1" style="height:4px;width:80px;margin-left:auto">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                    </td>
                    <td><?= e((string)$adv['start_date']) ?></td>
                    <td class="text-center"><span class="badge bg-<?= $sc ?>"><?= e((string)$adv['status']) ?></span></td>
                    <td><?= e((string)($adv['approved_by_name'] ?? '—')) ?></td>
                    <td class="text-end">
                        <?php if ($adv['status'] === 'Pending' && in_array(current_user()['role'] ?? '', ['Super Admin','Finance Officer'], true)): ?>
                            <form method="post" action="<?= e(base_url('salary-advance/approve/' . (string)$adv['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <input type="hidden" name="action" value="approve">
                                <button class="btn btn-sm btn-outline-success" onclick="return confirm('Approve this advance?')">Approve</button>
                            </form>
                            <form method="post" action="<?= e(base_url('salary-advance/approve/' . (string)$adv['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this advance?')">Reject</button>
                            </form>
                        <?php elseif ($adv['status'] === 'Active' && in_array(current_user()['role'] ?? '', ['Super Admin','Finance Officer'], true)): ?>
                            <form method="post" action="<?= e(base_url('salary-advance/cancel/' . (string)$adv['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this advance?')">Cancel</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

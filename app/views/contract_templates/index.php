<?php
$typeColors = [
    'Permanent' => 'primary',
    'Contract'  => 'warning',
    'Part-Time' => 'info',
    'Temporary' => 'secondary',
];
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Contract Templates</h2>
        <p class="text-gray mb-0">Create reusable contract wording with details the system fills automatically.</p>
    </div>
    <a href="<?= e(base_url('contract_template/create')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>New Template
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<?php if (empty($templates)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-file-earmark-text" style="font-size:3rem;color:#cbd5e1"></i>
            <p class="text-gray mt-3 mb-1">No contract templates yet.</p>
            <p class="text-gray small">Create a template to generate dynamic contracts per salary structure and type.</p>
            <a href="<?= e(base_url('contract_template/create')) ?>" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-1"></i>Create First Template
            </a>
        </div>
    </div>
<?php else: ?>

<!-- Auto-filled details callout -->
<div class="alert border-0 mb-4 d-flex gap-3 align-items-start" style="background:#f0f9ff;border-left:4px solid #0284c7 !important;border-left-style:solid !important">
    <i class="bi bi-magic fs-18 mt-1 flex-shrink-0" style="color:#0284c7"></i>
    <div>
        <strong style="color:#0369a1">Auto-filled details are now available in the editor</strong>
        <div class="mt-1" style="font-size:.8rem;color:#475569;line-height:1.6">
            HR users can click friendly fields like Employee Name, Start Date, Monthly Salary, and Company Name.
            The system handles the technical placeholders behind the scenes.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th>Template Name</th>
                        <th>Salary Structure</th>
                        <th>Contract Type</th>
                        <th>Branch</th>
                        <th class="text-center">Default</th>
                        <th class="text-center">Last Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $tpl): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e((string) ($tpl['name'] ?? '')) ?></div>
                                <?php if ((int) ($tpl['is_default'] ?? 0) === 1): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis mt-1">
                                        <i class="bi bi-star-fill me-1"></i>Default Fallback
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tpl['structure_name'])): ?>
                                    <span class="badge bg-purple-subtle" style="background:#ede9fe;color:#5b21b6">
                                        <?= e((string) $tpl['structure_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray small">Any structure</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tpl['contract_type'])): ?>
                                    <?php $tc = $typeColors[$tpl['contract_type']] ?? 'secondary'; ?>
                                    <span class="badge bg-<?= $tc ?>-subtle text-<?= $tc ?>-emphasis">
                                        <?= e((string) $tpl['contract_type']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray small">Any type</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($tpl['branch_name']) ? e((string)$tpl['branch_name']) : '<span class="text-gray small">Any branch</span>' ?></td>
                            <td class="text-center">
                                <?php if ((int) ($tpl['is_default'] ?? 0) === 1): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <span class="text-gray">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-gray small">
                                <?= e((string) ($tpl['updated_at'] ?? '-')) ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= e(base_url('contract_template/preview/' . (string) $tpl['id'])) ?>"
                                   class="btn btn-sm btn-outline-secondary" target="_blank" title="Preview">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= e(base_url('contract_template/edit/' . (string) $tpl['id'])) ?>"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="post"
                                      action="<?= e(base_url('contract_template/delete/' . (string) $tpl['id'])) ?>"
                                      class="d-inline">
                                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this template? Existing contracts using it will keep their content.');">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


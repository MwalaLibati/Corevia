<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Letter Templates</h2>
        <p class="text-gray mb-0">Manage reusable HR letters with details the system fills automatically.</p>
    </div>
    <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Employees</a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="alert border-0 mb-4 d-flex gap-3 align-items-start" style="background:#f0f9ff;border-left:4px solid #0284c7 !important">
    <i class="bi bi-magic fs-18 mt-1 flex-shrink-0" style="color:#0284c7"></i>
    <div>
        <strong style="color:#0369a1">Templates are edited like normal documents</strong>
        <div class="mt-1" style="font-size:.82rem;color:#475569;line-height:1.6">
            Use friendly fields like Employee Name, Company Logo, New Designation, and Final Dues Net.
            HR does not need to type technical placeholders or HTML.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.88rem">
                <thead class="table-light">
                    <tr>
                        <th>Template</th>
                        <th>Saved Letters Use</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Last Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($templates ?? []) as $template): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e((string) ($template['letter_type'] ?? '')) ?></div>
                            <span class="text-gray small">Used from employee profiles</span>
                        </td>
                        <td><?= e((string) ($template['letter_type'] ?? 'Letter')) ?> - Employee Name</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark"><?= e((string) ($template['version'] ?? 1)) ?></span>
                        </td>
                        <td class="text-center text-gray small"><?= e((string) ($template['updated_at'] ?? '-')) ?></td>
                        <td class="text-end">
                            <a href="<?= e(base_url('employee-letter-template/preview/' . (string) $template['id'])) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Preview">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= e(base_url('employee-letter-template/edit/' . (string) $template['id'])) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

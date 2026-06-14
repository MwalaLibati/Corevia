<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit Workflow</h2>
        <p class="text-gray mb-0"><?= e((string)$definition['workflow_type']) ?></p>
    </div>
    <a href="<?= e(base_url('workflow/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('workflow/update/' . (string)$definition['id'])) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Workflow Name</label>
                    <input type="text" name="name" class="form-control" value="<?= e((string)$definition['name']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= (int)$definition['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= (int)$definition['is_active'] !== 1 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"><?= e((string)($definition['description'] ?? '')) ?></textarea>
                </div>
            </div>

            <h5>Approval Steps</h5>
            <p class="text-gray small">Steps run from top to bottom. The last valid step becomes the final approval step.</p>

            <div id="workflowSteps">
                <?php $rows = !empty($steps) ? $steps : [['step_name'=>'Approval','required_role'=>'Super Admin','action_label'=>'Approve']]; ?>
                <?php foreach ($rows as $step): ?>
                    <div class="row g-2 align-items-end border rounded p-2 mb-2 workflow-step-row">
                        <div class="col-md-4">
                            <label class="form-label">Step Name</label>
                            <input type="text" name="step_name[]" class="form-control" value="<?= e((string)$step['step_name']) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Required Role</label>
                            <select name="required_role[]" class="form-select">
                                <?php foreach (($roles ?? []) as $role): ?>
                                    <option value="<?= e((string)$role) ?>" <?= (string)$step['required_role'] === (string)$role ? 'selected' : '' ?>>
                                        <?= e((string)$role) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Button Label</label>
                            <input type="text" name="action_label[]" class="form-control" value="<?= e((string)($step['action_label'] ?? 'Approve')) ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100 remove-step">X</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addWorkflowStep">Add Step</button>

            <div>
                <button type="submit" class="btn btn-primary">Save Workflow</button>
                <a href="<?= e(base_url('workflow/index')) ?>" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const container = document.getElementById('workflowSteps');
    const add = document.getElementById('addWorkflowStep');
    function bindRemove(row) {
        row.querySelector('.remove-step')?.addEventListener('click', function(){
            if (container.querySelectorAll('.workflow-step-row').length > 1) {
                row.remove();
            }
        });
    }
    container.querySelectorAll('.workflow-step-row').forEach(bindRemove);
    add?.addEventListener('click', function(){
        const first = container.querySelector('.workflow-step-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(function(input){ input.value = ''; });
        bindRemove(clone);
        container.appendChild(clone);
    });
})();
</script>

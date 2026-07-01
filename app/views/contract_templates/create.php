<?php
$isEdit   = !empty($template);
$formData = !empty($old) ? $old : ($template ?? []);
$postUrl  = $isEdit
    ? base_url('contract_template/update/' . (string) ($template['id'] ?? ''))
    : base_url('contract_template/store');
$fieldGroups = $fieldGroups ?? [];
$versions = $versions ?? [];
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark"><?= $isEdit ? 'Edit Contract Template' : 'New Contract Template' ?></h2>
        <p class="text-gray mb-0">
            <?= $isEdit
                ? e((string) ($template['name'] ?? ''))
                : 'Design a reusable contract document with details the system fills automatically.' ?>
        </p>
    </div>
    <a href="<?= e(base_url('contract_template/index')) ?>" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e($postUrl) ?>" id="templateForm">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <input type="hidden" name="body" id="bodyInput">

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= e((string) ($formData['name'] ?? '')) ?>"
                                   placeholder="e.g. Employee Contract" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Salary Structure</label>
                            <select name="salary_structure_id" class="form-select">
                                <option value="">Any structure</option>
                                <?php foreach ($structures as $s): ?>
                                    <option value="<?= e((string) $s['id']) ?>"
                                        <?= ((string)($formData['salary_structure_id'] ?? '') === (string)$s['id']) ? 'selected' : '' ?>>
                                        <?= e((string) $s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-gray">Leave blank to match any structure.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Contract Type</label>
                            <select name="contract_type" class="form-select">
                                <option value="">Any type</option>
                                <?php foreach ($contractTypes as $ct): ?>
                                    <option value="<?= e($ct) ?>"
                                        <?= (($formData['contract_type'] ?? '') === $ct) ? 'selected' : '' ?>>
                                        <?= e($ct) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-gray">Leave blank to match any contract type.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Any branch</option>
                                <?php foreach (($branches ?? []) as $branch): ?>
                                    <option value="<?= e((string)$branch['id']) ?>" <?= ((string)($formData['branch_id'] ?? '') === (string)$branch['id']) ? 'selected' : '' ?>>
                                        <?= e((string)$branch['name']) ?><?= !empty($branch['code']) ? ' (' . e((string)$branch['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-gray">Leave blank for a company-wide template.</small>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefault"
                                    <?= (int)($formData['is_default'] ?? 0) === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isDefault">
                                    <strong>Set as default fallback template</strong>
                                    <span class="text-gray ms-1">used when no other template matches</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <label class="form-label fw-semibold">Contract Wording <span class="text-danger">*</span></label>
                    <p class="text-gray small mb-2">
                        Write normally. Use the auto-filled detail buttons on the right when the system should fill in a value.
                    </p>

                    <div id="quillToolbar">
                        <span class="ql-formats">
                            <select class="ql-header">
                                <option selected></option>
                                <option value="1"></option>
                                <option value="2"></option>
                                <option value="3"></option>
                            </select>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-bold"></button>
                            <button class="ql-italic"></button>
                            <button class="ql-underline"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-list" value="ordered"></button>
                            <button class="ql-list" value="bullet"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-indent" value="-1"></button>
                            <button class="ql-indent" value="+1"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-align" value=""></button>
                            <button class="ql-align" value="center"></button>
                            <button class="ql-align" value="right"></button>
                            <button class="ql-align" value="justify"></button>
                        </span>
                        <span class="ql-formats">
                            <button class="ql-clean"></button>
                        </span>
                    </div>
                    <div id="quillEditor" style="min-height:520px;font-family:'Times New Roman',serif;font-size:12pt;border:1px solid #dee2e6;border-top:none"></div>

                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Template' ?>
                        </button>
                        <a href="<?= e(base_url('contract_template/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
                        <?php if ($isEdit): ?>
                            <a href="<?= e(base_url('contract_template/preview/' . (string) ($template['id'] ?? ''))) ?>"
                               class="btn btn-outline-dark" target="_blank">
                                <i class="bi bi-eye me-1"></i>Preview
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-magic me-2" style="color:#7c3aed"></i>Auto-Filled Details</h6>
                <p class="text-gray small mb-0 mt-1">Click a detail to place it in the contract. The system fills the real value later.</p>
            </div>
            <div class="card-body pt-2">
                <?php foreach ($fieldGroups as $groupName => $fields): ?>
                    <div class="mb-3">
                        <div class="text-uppercase text-gray fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.04em">
                            <?= e((string)$groupName) ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($fields as $tok => $label): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary token-btn"
                                        data-token="{{<?= e((string)$tok) ?>}}"
                                        title="Auto-fills: <?= e((string)$label) ?>"
                                        style="font-size:.74rem">
                                    <?= e((string)$label) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-white border-0 pb-3">
                <p class="text-gray small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Example: inserting <strong>Employee Name</strong> means each generated contract uses that employee's actual name.
                </p>
            </div>
        </div>

        <?php if ($isEdit && !empty($versions)): ?>
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Saved Versions</h6>
                    <p class="text-gray small mb-0 mt-1">Previous wording is kept whenever this template is edited.</p>
                </div>
                <div class="card-body pt-2">
                    <?php foreach (array_slice($versions, 0, 5) as $version): ?>
                        <div class="d-flex justify-content-between border-bottom py-2" style="font-size:.78rem">
                            <span>Version <?= (int)($version['version'] ?? 1) ?></span>
                            <span class="text-gray"><?= e((string)($version['created_at'] ?? '')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#quillEditor', {
        modules: { toolbar: '#quillToolbar' },
        theme: 'snow',
        placeholder: 'Write your contract wording here. Use auto-filled details like Employee Name or Monthly Salary from the right...'
    });

    <?php
    $existingBody = (string) ($formData['body'] ?? '');
    if ($existingBody !== ''):
    ?>
    quill.root.innerHTML = <?= json_encode($existingBody) ?>;
    <?php endif; ?>

    document.querySelectorAll('.token-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var token = this.getAttribute('data-token');
            var range = quill.getSelection(true);
            var index = range ? range.index : quill.getLength();
            quill.insertText(index, token, { color: '#5b21b6', bold: true }, Quill.sources.USER);
            quill.insertText(index + token.length, ' ', Quill.sources.USER);
            quill.setSelection(index + token.length + 1);
        });
    });

    document.getElementById('templateForm').addEventListener('submit', function () {
        document.getElementById('bodyInput').value = quill.root.innerHTML;
    });
});
</script>

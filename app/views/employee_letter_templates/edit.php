<?php
$template = $template ?? [];
$old = $old ?? [];
$formBody = (string) ($old['body_html'] ?? $template['body_html'] ?? '');
$fieldGroups = $fieldGroups ?? [];
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Edit <?= e((string) ($template['letter_type'] ?? 'Letter')) ?></h2>
        <p class="text-gray mb-0">Write the letter normally and insert auto-filled details from the right.</p>
    </div>
    <a href="<?= e(base_url('employee-letter-template/index')) ?>" class="btn btn-outline-secondary">Back</a>
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
                <form method="post" action="<?= e(base_url('employee-letter-template/update/' . (string) ($template['id'] ?? 0))) ?>" id="letterTemplateForm">
                    <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">
                    <input type="hidden" name="body_html" id="bodyInput">

                    <label class="form-label fw-semibold">Letter Content</label>
                    <p class="text-gray small mb-2">Use formatting tools and click auto-filled details. No code is required.</p>

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
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                        <a href="<?= e(base_url('employee-letter-template/preview/' . (string) ($template['id'] ?? 0))) ?>" class="btn btn-outline-dark" target="_blank">
                            <i class="bi bi-eye me-1"></i>Preview
                        </a>
                        <a href="<?= e(base_url('employee-letter-template/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top:80px">
            <div class="card-header bg-white border-0 pt-3 pb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-magic me-2" style="color:#7c3aed"></i>Auto-Filled Details</h6>
                <p class="text-gray small mb-0 mt-1">Click a detail to place it in the letter.</p>
            </div>
            <div class="card-body pt-2">
                <?php foreach ($fieldGroups as $groupName => $fields): ?>
                    <div class="mb-3">
                        <div class="text-uppercase text-gray fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.04em">
                            <?= e((string) $groupName) ?>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <?php foreach ($fields as $token => $label): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary token-btn" data-token="<?= e((string) $token) ?>" style="font-size:.74rem">
                                    <?= e((string) $label) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer bg-white border-0 pb-3">
                <p class="text-gray small mb-0">
                    Example: inserting <strong>Employee Name</strong> means each generated letter uses that employee's real name.
                </p>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var quill = new Quill('#quillEditor', {
        modules: { toolbar: '#quillToolbar' },
        theme: 'snow',
        placeholder: 'Write the letter here. Use auto-filled details from the right...'
    });

    quill.root.innerHTML = <?= json_encode($formBody) ?>;

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

    document.getElementById('letterTemplateForm').addEventListener('submit', function () {
        document.getElementById('bodyInput').value = quill.root.innerHTML;
    });
});
</script>

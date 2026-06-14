<?php
$templates = $templates ?? [];
$types = $types ?? [];
$tokens = $tokens ?? [];
$byType = [];
foreach ($templates as $template) {
    $byType[(string) ($template['letter_type'] ?? '')] = $template;
}
?>

<div class="d-flex align-items-center justify-content-between mr-bottom-30">
    <div>
        <h2 class="text-dark">Employee Letter Templates</h2>
        <p class="text-gray mb-0">Control the wording used for promotion, transfer, confirmation, termination, and certificate letters.</p>
    </div>
    <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Back to Employees</a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= e((string) $flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= e((string) $flashError) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <form method="post" action="<?= e(base_url('employee/updateLetterTemplates')) ?>" id="letterTemplateForm">
            <input type="hidden" name="_csrf" value="<?= e((string) $csrf) ?>">

            <div class="accordion" id="letterTemplateAccordion">
                <?php foreach ($types as $index => $type): ?>
                    <?php
                        $template = $byType[(string) $type] ?? [];
                        $title = (string) ($template['title'] ?? ((string) $type . ' - {{employee_name}}'));
                        $body = (string) ($template['body_html'] ?? EmployeeLetterTemplate::defaultBody((string) $type));
                    ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white d-flex align-items-center justify-content-between gap-3">
                            <button class="btn btn-link text-decoration-none fw-semibold text-dark p-0" type="button" data-bs-toggle="collapse" data-bs-target="#letterTemplate<?= (int) $index ?>">
                                <?= e((string) $type) ?>
                            </button>
                            <span class="badge bg-light text-dark">Version <?= e((string) ($template['version'] ?? 1)) ?></span>
                        </div>
                        <div id="letterTemplate<?= (int) $index ?>" class="collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#letterTemplateAccordion">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Generated Letter Title</label>
                                    <input type="text" name="title[<?= e((string) $type) ?>]" class="form-control" value="<?= e($title) ?>">
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Letter Content</label>
                                    <textarea name="body_html[<?= e((string) $type) ?>]" class="form-control letter-template-body" rows="18"><?= e($body) ?></textarea>
                                    <div class="form-text">You can use simple HTML such as paragraphs, headings, tables, and bold text. The fields on the right are automatically filled when a letter is generated.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-2 justify-content-end mt-3">
                <a href="<?= e(base_url('employee/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Save Letter Templates
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm position-sticky" style="top:16px">
            <div class="card-body">
                <h5 class="mb-2">Auto-Filled Fields</h5>
                <p class="text-gray small">Click a field to insert it into the currently selected letter content.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tokens as $label => $token): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary token-insert" data-token="<?= e((string) $token) ?>">
                            <?= e((string) $label) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <hr>
                <div class="small text-gray">
                    These templates are company-specific. Generated letters keep the wording used at the time they are created, even if the template is edited later.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var activeTextarea = document.querySelector('.letter-template-body');
    document.querySelectorAll('.letter-template-body').forEach(function (textarea) {
        textarea.addEventListener('focus', function () {
            activeTextarea = textarea;
        });
    });
    document.querySelectorAll('.token-insert').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!activeTextarea) return;
            var token = button.getAttribute('data-token') || '';
            var start = activeTextarea.selectionStart || 0;
            var end = activeTextarea.selectionEnd || 0;
            var value = activeTextarea.value;
            activeTextarea.value = value.slice(0, start) + token + value.slice(end);
            activeTextarea.focus();
            activeTextarea.selectionStart = activeTextarea.selectionEnd = start + token.length;
        });
    });
})();
</script>

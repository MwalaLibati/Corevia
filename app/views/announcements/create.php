<div class="mb-4">
    <h2 class="text-dark">New Announcement</h2>
    <p class="text-gray mb-0">Post a notice visible to all employees on the portal.</p>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:760px">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('announcement/store')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Title *</label>
                <input type="text" name="title" class="form-control" required
                       value="<?= e((string)($old['title'] ?? '')) ?>"
                       placeholder="e.g. Public Holiday Notice">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Body *</label>
                <textarea name="body" class="form-control" rows="8" required
                          placeholder="Full announcement text…"><?= e((string)($old['body'] ?? '')) ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Expires On <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="expires_at" class="form-control"
                           min="<?= date('Y-m-d') ?>"
                           value="<?= e((string)($old['expires_at'] ?? '')) ?>">
                    <div class="form-text">Leave blank to never expire.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="chkPublish"
                               <?= !empty($old['is_published']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="chkPublish">
                            <strong>Publish immediately</strong>
                            <div class="text-muted" style="font-size:.78rem">Employees will see this on the portal right away.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Save Announcement</button>
                <a href="<?= e(base_url('announcement/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

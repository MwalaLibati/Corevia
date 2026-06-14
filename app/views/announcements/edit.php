<div class="mb-4">
    <h2 class="text-dark">Edit Announcement</h2>
    <p class="text-gray mb-0">Update the announcement details.</p>
</div>

<?php if (!empty($flashError)): ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:760px">
    <div class="card-body">
        <form method="post" action="<?= e(base_url('announcement/update/' . (int)$item['id'])) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

            <div class="mb-3">
                <label class="form-label fw-semibold">Title *</label>
                <input type="text" name="title" class="form-control" required
                       value="<?= e((string)$item['title']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Body *</label>
                <textarea name="body" class="form-control" rows="8" required><?= e((string)$item['body']) ?></textarea>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Expires On <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="expires_at" class="form-control"
                           value="<?= e((string)($item['expires_at'] ?? '')) ?>">
                </div>
                <div class="col-md-6 d-flex align-items-end pb-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="chkPublish"
                               <?= $item['is_published'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="chkPublish">
                            <strong>Published</strong>
                            <div class="text-muted" style="font-size:.78rem">Visible to all employees on the portal.</div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button>
                <a href="<?= e(base_url('announcement/index')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

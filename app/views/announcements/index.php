<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">Announcements</h2>
        <p class="text-gray mb-0">Manage noticeboard posts visible to employees on the portal.</p>
    </div>
    <a href="<?= e(base_url('announcement/create')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Announcement
    </a>
</div>

<?php if (!empty($flashError)):   ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Posted By</th>
                        <th class="text-center">Status</th>
                        <th>Expires</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No announcements yet.</td></tr>
                <?php else: foreach ($announcements as $a):
                    $expired = $a['expires_at'] && $a['expires_at'] < date('Y-m-d');
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e((string)$a['title']) ?></div>
                        <div class="text-muted" style="font-size:.78rem;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e(strip_tags((string)$a['body'])) ?></div>
                    </td>
                    <td><?= e((string)($a['posted_by_name'] ?? 'System')) ?></td>
                    <td class="text-center">
                        <?php if ($expired): ?>
                            <span class="badge bg-secondary">Expired</span>
                        <?php elseif ($a['is_published']): ?>
                            <span class="badge bg-success">Published</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $a['expires_at'] ? e((string)$a['expires_at']) : '<span class="text-muted">Never</span>' ?></td>
                    <td><?= e(date('d M Y', strtotime((string)$a['created_at']))) ?></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <!-- Toggle publish -->
                            <form method="post" action="<?= e(base_url('announcement/togglePublish/' . (int)$a['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-sm <?= $a['is_published'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                        title="<?= $a['is_published'] ? 'Unpublish' : 'Publish' ?>">
                                    <i class="bi bi-<?= $a['is_published'] ? 'eye-slash' : 'send' ?>"></i>
                                </button>
                            </form>
                            <a href="<?= e(base_url('announcement/edit/' . (int)$a['id'])) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="<?= e(base_url('announcement/delete/' . (int)$a['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete this announcement?')" title="Delete">
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

<div class="mb-4">
    <h2 class="text-dark">Noticeboard</h2>
    <p class="text-gray mb-0">Announcements and notices from management.</p>
</div>

<?php if (empty($announcements)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-megaphone" style="font-size:2.5rem;color:#cbd5e1"></i>
            <p class="text-muted mt-3 mb-0">No announcements at the moment. Check back later.</p>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-3">
    <?php foreach ($announcements as $a): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0" style="width:42px;height:42px;background:#eff6ff;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:1.2rem">
                        <i class="bi bi-megaphone"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="mb-0 fw-bold"><?= e((string)$a['title']) ?></h6>
                            <?php if ($a['expires_at']): ?>
                                <span class="badge bg-light text-muted border" style="font-size:.7rem">
                                    Expires <?= e(date('d M Y', strtotime((string)$a['expires_at']))) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.83rem;color:#374151;line-height:1.6;white-space:pre-wrap"><?= e((string)$a['body']) ?></div>
                        <div class="text-muted mt-2" style="font-size:.75rem">
                            Posted by <?= e((string)($a['posted_by_name'] ?? 'Management')) ?>
                            &bull; <?= e(date('d M Y', strtotime((string)$a['created_at']))) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

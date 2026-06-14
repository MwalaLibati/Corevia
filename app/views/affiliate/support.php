<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div><h4 class="mb-0">Support & Messages</h4><p class="text-muted mb-0 small">Updates from Corevia and support tickets.</p></div>
</div>
<?php if (!empty($flash)): ?><div class="alert alert-success"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if (!empty($flashErr)): ?><div class="alert alert-danger"><?= e((string)$flashErr) ?></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-5"><div class="card border-0 shadow-sm"><div class="card-body">
        <h6 class="fw-bold mb-3">New Support Ticket</h6>
        <form method="post" action="<?= e(base_url('affiliate/dashboard/ticketStore')) ?>">
            <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
            <div class="mb-3"><label class="form-label">Subject</label><input name="subject" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option>Normal</option><option>Low</option><option>High</option></select></div>
            <div class="mb-3"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="4" required></textarea></div>
            <button class="btn text-white" style="background:#7c3aed">Submit Ticket</button>
        </form>
    </div></div></div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-4"><div class="card-body">
            <h6 class="fw-bold mb-3">Messages</h6>
            <?php foreach ($messages as $m): ?><div class="border-bottom py-2"><strong><?= e((string)$m['subject']) ?></strong><div class="text-muted small"><?= e((string)$m['created_at']) ?></div><p class="mb-0"><?= nl2br(e((string)$m['message'])) ?></p></div><?php endforeach; if (empty($messages)): ?><p class="text-muted text-center py-4 mb-0">No messages yet.</p><?php endif; ?>
        </div></div>
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h6 class="fw-bold mb-3">Tickets</h6>
            <?php foreach ($tickets as $t): ?><div class="border-bottom py-2"><strong><?= e((string)$t['ticket_number']) ?> - <?= e((string)$t['subject']) ?></strong><div class="text-muted small"><?= e((string)$t['status']) ?> &bull; <?= e((string)$t['priority']) ?></div><?php if (!empty($t['admin_response'])): ?><p class="mb-0"><?= nl2br(e((string)$t['admin_response'])) ?></p><?php endif; ?></div><?php endforeach; if (empty($tickets)): ?><p class="text-muted text-center py-4 mb-0">No support tickets yet.</p><?php endif; ?>
        </div></div>
    </div>
</div>

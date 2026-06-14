<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="text-dark">My Documents</h2>
        <p class="text-gray mb-0">Upload and manage your personal documents.</p>
    </div>
    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#uploadDocForm">
        <i class="bi bi-upload me-1"></i> Upload Document
    </button>
</div>

<?php if (!empty($flashError)):   ?><div class="alert alert-danger"><?= e((string)$flashError) ?></div><?php endif; ?>
<?php if (!empty($flashSuccess)): ?><div class="alert alert-success"><?= e((string)$flashSuccess) ?></div><?php endif; ?>

<?php
$uploadedTypes = array_map(static fn(array $doc): string => (string) ($doc['document_type'] ?? ''), $documents ?? []);
$missingRequired = [];
foreach (($requiredTypes ?? []) as $type) {
    if (!in_array((string) $type, $uploadedTypes, true)) {
        $missingRequired[] = (string) $type;
    }
}
?>

<?php if (!empty($missingRequired)): ?>
<div class="alert alert-warning">
    Required documents still missing: <?= e(implode(', ', $missingRequired)) ?>.
</div>
<?php endif; ?>

<!-- Upload form (collapsed) -->
<div class="collapse mb-4" id="uploadDocForm">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="mb-3"><i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Upload Document</h5>
            <form method="post" action="<?= e(base_url('portal/documentUpload')) ?>" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">

                <div class="col-md-5">
                    <label class="form-label">Document Type *</label>
                    <select name="document_type" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach ([
                            'NRC / National ID', 'Passport', 'Academic Certificate',
                            'Teaching Certificate', 'Medical Certificate', 'Bank Statement',
                            'NAPSA Statement', 'Contract Copy', 'Leave Supporting Doc', 'Other'
                        ] as $dt): ?>
                        <option><?= e($dt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-7">
                    <label class="form-label">File * <span class="text-muted">(PDF, JPG, PNG - max 5 MB)</span></label>
                    <input type="file" name="doc_file" class="form-control" required
                           accept=".pdf,.jpg,.jpeg,.png">
                </div>

                <div class="col-12">
                    <label class="form-label">Notes <span class="text-muted">(optional)</span></label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. NRC for verification">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Upload</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#uploadDocForm">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Documents list -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if (empty($documents)): ?>
            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No documents uploaded yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>File Name</th>
                        <th>Size</th>
                        <th>Notes</th>
                        <th>Uploaded</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($documents as $doc):
                    $sizeKb = $doc['file_size'] ? round($doc['file_size'] / 1024, 1) . ' KB' : '-';
                    $isImg  = in_array($doc['mime_type'], ['image/jpeg','image/png','image/gif'], true);
                    $isPdf  = $doc['mime_type'] === 'application/pdf';
                    $icon   = $isImg ? 'bi-file-image' : ($isPdf ? 'bi-file-pdf' : 'bi-file-earmark');
                ?>
                <tr>
                    <td>
                        <i class="bi <?= $icon ?> me-1 text-primary"></i>
                        <?= e((string)$doc['document_type']) ?>
                    </td>
                    <td style="font-size:.83rem"><?= e((string)$doc['file_name']) ?></td>
                    <td><?= $sizeKb ?></td>
                    <td class="text-muted" style="font-size:.82rem"><?= e((string)($doc['notes'] ?? '-')) ?></td>
                    <td style="font-size:.82rem"><?= e(date('d M Y', strtotime((string)$doc['created_at']))) ?></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="<?= e(base_url('portal/documentDownload/' . (int)$doc['id'])) ?>"
                               class="btn btn-sm btn-outline-primary" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                            <form method="post" action="<?= e(base_url('portal/documentDelete/' . (int)$doc['id'])) ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= e((string)$csrf) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete this document?')" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Company Documents</h5>
                <?php if (empty($contracts)): ?>
                    <p class="text-muted mb-0">No approved contracts are available yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($contracts as $contract): ?>
                            <?php if ((string) ($contract['approval_status'] ?? '') !== 'Approved') { continue; } ?>
                            <a class="list-group-item list-group-item-action px-0" href="<?= e(base_url('portal/contractView/' . (string) $contract['id'])) ?>">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= e((string) ($contract['contract_type'] ?? 'Employment Contract')) ?></strong>
                                    <span class="badge bg-light text-dark border"><?= e((string) ($contract['status'] ?? '')) ?></span>
                                </div>
                                <div class="text-muted small"><?= e((string) ($contract['contract_number'] ?? '')) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="mb-3"><i class="bi bi-envelope-paper me-2 text-primary"></i>Letters & Certificates</h5>
                <?php if (empty($generatedLetters)): ?>
                    <p class="text-muted mb-0">No generated letters or certificates are available yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($generatedLetters as $letter): ?>
                            <a class="list-group-item list-group-item-action px-0" href="<?= e(base_url('portal/letterView/' . (string) $letter['id'])) ?>">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= e((string) ($letter['title'] ?? 'Letter')) ?></strong>
                                    <span class="text-muted small"><?= e(!empty($letter['generated_at']) ? date('d M Y', strtotime((string) $letter['generated_at'])) : '') ?></span>
                                </div>
                                <div class="text-muted small"><?= e((string) ($letter['letter_type'] ?? '')) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

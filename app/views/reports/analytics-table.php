<div class="d-flex align-items-center justify-content-between mr-bottom-30 flex-wrap gap-3">
    <div>
        <h2 class="text-dark"><?= e((string) ($reportTitle ?? $title ?? 'Report')) ?></h2>
        <p class="text-gray mb-0"><?= e((string) ($description ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php $reportMethod = (string) ($method ?? $slug ?? 'index'); ?>
        <a href="<?= e(base_url('report/' . $reportMethod . '?export=csv')) ?>" class="btn btn-outline-primary"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
        <a href="<?= e(base_url('report/' . $reportMethod . '?export=xls')) ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        <a href="<?= e(base_url('report/' . $reportMethod . '?export=pdf')) ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="<?= e(base_url('report/index')) ?>" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<?php if (!empty($chartKeys) && !empty($rows)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <canvas id="reportTrendChart" style="max-height:320px"></canvas>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead>
                <tr>
                    <?php foreach (($headers ?? []) as $header): ?>
                        <th><?= e((string) $header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= max(1, count($headers ?? [])) ?>" class="text-center text-gray py-5">
                        <div class="ent-empty-state">
                            <i class="bi bi-inbox"></i>
                            <div>No data found for this report yet.</div>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= is_float($cell) || is_int($cell) ? e(is_float($cell) ? number_format($cell, 2) : (string) $cell) : e((string) $cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($chartKeys) && !empty($rows)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (typeof Chart === 'undefined') return;
    var rows = <?= json_encode(array_values($rows), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    var labels = rows.map(function(row){ return row[0]; });
    var datasets = [];
    if (rows[0] && rows[0].length >= 4) {
        datasets.push({label: 'Primary', data: rows.map(function(row){ return Number(row[row.length - 2] || 0); }), borderColor: '#1d4ed8', backgroundColor: 'rgba(29,78,216,.08)', tension: .35});
        datasets.push({label: 'Secondary', data: rows.map(function(row){ return Number(row[row.length - 1] || 0); }), borderColor: '#059669', backgroundColor: 'rgba(5,150,105,.08)', tension: .35});
    }
    new Chart(document.getElementById('reportTrendChart'), {type:'line', data:{labels:labels,datasets:datasets}, options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}}}});
});
</script>
<?php endif; ?>

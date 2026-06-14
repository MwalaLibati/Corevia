<?php
$template = $template ?? [];
$renderedBody = $renderedBody ?? '';
$missingFields = $missingFields ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Letter Preview - <?= e((string) ($template['letter_type'] ?? 'Preview')) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.72;color:#111;background:#e8e8e8}
.page{width:210mm;min-height:297mm;margin:70px auto 30px;background:#fff;padding:20mm 22mm 18mm;box-shadow:0 2px 20px rgba(0,0,0,.18)}
.action-bar{position:fixed;top:0;left:0;right:0;background:#312e81;color:#fff;display:flex;align-items:center;gap:10px;padding:9px 20px;z-index:999;font-family:Arial,sans-serif;font-size:13px}
.action-bar strong{flex:1}
.action-bar button,.action-bar a{padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block}
.btn-print{background:#fff;color:#312e81;font-weight:600}
.btn-edit{background:#7c3aed;color:#fff;font-weight:600}
.btn-back{background:transparent;color:#ddd;border:1px solid #666 !important}
.preview-badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:3px 10px;font-size:.75rem;font-weight:600}
.warning-bar{position:fixed;top:42px;left:0;right:0;background:#fff7ed;color:#9a3412;border-bottom:1px solid #fed7aa;padding:7px 20px;font-family:Arial,sans-serif;font-size:12px;z-index:998}
.page h1,.page h2,.page h3{margin:14px 0 8px}
.page p{margin-bottom:8px}
.page ul,.page ol{margin:4px 0 10px 28px}
@media print{body{background:#fff}.action-bar,.warning-bar{display:none}.page{margin:0;box-shadow:none;padding:15mm 18mm}}
</style>
</head>
<body>
<div class="action-bar">
    <strong>Preview - <?= e((string) ($template['letter_type'] ?? 'Letter')) ?> <span class="preview-badge">SAMPLE DATA</span></strong>
    <button class="btn-back" onclick="window.close()">Close</button>
    <a href="<?= e(base_url('employee-letter-template/edit/' . (string) ($template['id'] ?? 0))) ?>" class="btn-edit">Edit Template</a>
    <button class="btn-print" onclick="window.print()">Print</button>
</div>
<?php if (!empty($missingFields)): ?>
    <div class="warning-bar">This template contains unsupported fields: <?= e(implode(', ', $missingFields)) ?>.</div>
<?php endif; ?>
<div class="page">
    <?= $renderedBody ?>
</div>
</body>
</html>

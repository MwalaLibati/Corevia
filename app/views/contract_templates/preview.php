<?php
$template     = $template ?? [];
$renderedBody = $renderedBody ?? '';
$renderedCover = $renderedCover ?? '';
$renderedSignature = $renderedSignature ?? '';
$renderedFooter = $renderedFooter ?? '';
$missingFields = $missingFields ?? [];
$tokenValues = $tokenValues ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Template Preview &mdash; <?= e((string)($template['name'] ?? 'Preview')) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.72;color:#111;background:#e8e8e8}
.page{width:210mm;min-height:297mm;margin:70px auto 30px;background:#fff;padding:20mm 22mm 24mm;box-shadow:0 2px 20px rgba(0,0,0,.18);position:relative}
.cover-page{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;page-break-after:always}
.cover-logo{max-height:110px;max-width:260px;object-fit:contain;margin-bottom:28px}.cover-company{font-size:20pt;font-weight:700;text-transform:uppercase;color:#153e2b}.cover-rule{width:70mm;border-top:3px solid #153e2b;margin:24px auto}.cover-title{font-size:25pt;font-weight:700;text-transform:uppercase}.cover-employee{font-size:15pt;font-weight:700;margin-top:30px;text-transform:uppercase}.cover-meta{font-size:11pt;color:#4b5563;margin-top:10px;line-height:1.7}.print-footer{display:none}
.cover-content{width:100%;text-align:center}.cover-content img{max-height:110px!important;max-width:260px!important;margin-bottom:28px}
.action-bar{position:fixed;top:0;left:0;right:0;background:#312e81;color:#fff;display:flex;align-items:center;gap:10px;padding:9px 20px;z-index:999;font-family:Arial,sans-serif;font-size:13px}
.action-bar strong{flex:1}
.action-bar button,.action-bar a{padding:6px 14px;border:none;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;display:inline-block}
.btn-print{background:#fff;color:#312e81;font-weight:600}
.btn-edit{background:#7c3aed;color:#fff;font-weight:600}
.btn-back{background:transparent;color:#ccc;border:1px solid #666 !important}
.preview-badge{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:4px;padding:3px 10px;font-size:.75rem;font-weight:600;letter-spacing:.5px}
.warning-bar{position:fixed;top:42px;left:0;right:0;background:#fff7ed;color:#9a3412;border-bottom:1px solid #fed7aa;padding:7px 20px;font-family:Arial,sans-serif;font-size:12px;z-index:998}
.missing-field{background:#fff7ed;border:1px solid #fb923c;color:#9a3412;border-radius:3px;padding:0 4px;font-family:Arial,sans-serif;font-size:10pt}
.letterhead{text-align:center;border-bottom:3px double #1f5136;padding-bottom:12px;margin-bottom:18px}
.letterhead .doc-logo{max-height:74px;max-width:150px;object-fit:contain;margin-bottom:8px}
.letterhead .company-name{font-size:16pt;font-weight:bold;letter-spacing:.8px;text-transform:uppercase;color:#1f5136}
.letterhead .doc-title{font-size:13pt;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;margin-top:5px}
.signature-section{margin-top:30px;border-top:2px solid #1f5136;padding-top:16px}
.sig-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:16px}
.sig-line{border-bottom:1px solid #333;margin:28px 0 4px;width:85%}
.sig-role{font-weight:bold;text-transform:uppercase;font-size:10pt;color:#1f5136;margin-bottom:10px}
.page h1,.page h2,.page h3{font-size:12pt;font-weight:bold;text-transform:uppercase;margin:18px 0 6px}
.page p{margin-bottom:8px;text-align:justify}
.page ul{margin:4px 0 10px 28px}
.page ul li{margin-bottom:3px}
.page ol{margin:4px 0 10px 28px}
.page ol li{margin-bottom:4px}
.page strong{font-weight:bold}
.page em{font-style:italic}
.page table{width:100%;border-collapse:collapse;margin:14px 0 20px;page-break-inside:avoid}.page table th,.page table td{border:1px solid #64748b;padding:8px 10px;vertical-align:top;text-align:left}.page table th{background:#f1f5f9;font-weight:700}.page table.signature-table td{height:135px;padding:14px}
@media print{
    @page{size:A4;margin:15mm 18mm 22mm}
    body{background:#fff}
    .action-bar{display:none}
    .warning-bar{display:none}
    .page{width:auto;min-height:auto;margin:0;box-shadow:none;padding:0}.cover-page{min-height:245mm}
    .print-footer{display:flex;position:fixed;left:18mm;right:18mm;bottom:7mm;border-top:1px solid #94a3b8;padding-top:4px;justify-content:space-between;font-size:8.5pt;color:#475569}.print-footer .page-number::after{content:'Page ' counter(page) ' of ' counter(pages)}
}
</style>
</head>
<body>

<div class="action-bar">
    <strong>
        Preview &mdash; <?= e((string)($template['name'] ?? '')) ?>
        &nbsp;<span class="preview-badge">DUMMY DATA</span>
    </strong>
    <button class="btn-back" onclick="window.close()">&#8592; Close</button>
    <a href="<?= e(base_url('contract_template/edit/' . (string)($template['id'] ?? ''))) ?>"
       class="btn-edit">&#9998; Edit Template</a>
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
</div>

<?php if (!empty($missingFields)): ?>
    <div class="warning-bar">
        This preview needs missing employee or company details:
        <?= e(implode(', ', array_values($missingFields))) ?>.
    </div>
<?php endif; ?>

<div class="page cover-page">
    <?php if ($renderedCover !== ''): ?>
        <?= $renderedCover ?>
    <?php else: ?>
    <?php if (!empty($tokenValues['company_logo_url'])): ?><img src="<?= e((string)$tokenValues['company_logo_url']) ?>" class="cover-logo" alt="Company logo"><?php endif; ?>
    <div class="cover-company"><?= e((string)($tokenValues['company_name'] ?? 'Company Name')) ?></div><div class="cover-rule"></div>
    <div class="cover-title">Employment Contract</div><div class="cover-employee"><?= e((string)($tokenValues['employee_name'] ?? 'Employee Name')) ?></div>
    <div class="cover-meta">Employee No: <?= e((string)($tokenValues['employee_number'] ?? '-')) ?><br>Position: <?= e((string)($tokenValues['designation'] ?? '-')) ?><br>Contract Reference: <?= e((string)($tokenValues['contract_number'] ?? '-')) ?><br>Effective Date: <?= e((string)($tokenValues['start_date'] ?? '-')) ?></div>
    <?php endif; ?>
</div>

<div class="page">
    <?= $renderedBody ?>
    <?php if ($renderedSignature !== ''): ?><div class="signature-section"><?= $renderedSignature ?></div><?php endif; ?>
</div>

<div class="print-footer"><span><?= $renderedFooter !== '' ? $renderedFooter : e((string)($tokenValues['company_name'] ?? 'Company')) ?></span><span class="page-number"></span></div>

</body>
</html>

<?php
$contract     = $contract ?? [];
$employee     = $employee ?? [];
$downloadName = $downloadName ?? 'contract';
$renderedBody = $renderedBody ?? null;
$renderedCover = $renderedCover ?? null;
$renderedSignature = $renderedSignature ?? null;
$renderedFooter = $renderedFooter ?? null;
$missingFields = $missingFields ?? [];

function contractOrdinal(int $n): string {
    $s = ['th','st','nd','rd'];
    $v = $n % 100;
    return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
}
function contractLongDate(?string $date): string {
    if (!$date) return '________________';
    $d = date_create($date);
    return $d ? contractOrdinal((int)$d->format('j')) . ' day of ' . $d->format('F Y') : $date;
}
function contractShortDate(?string $date): string {
    if (!$date) return '________________';
    $d = date_create($date);
    return $d ? $d->format('j F Y') : $date;
}
function contractPeriodText(?string $start, ?string $end): string {
    if (!$start || !$end) { return '________________ months'; }
    $s = date_create($start); $e = date_create($end);
    if (!$s || !$e) { return '________________ months'; }
    $diff = $s->diff($e);
    $total = $diff->y * 12 + $diff->m;
    if ($total <= 0) { return '________________ months'; }
    $words = [1=>'one',2=>'two',3=>'three',4=>'four',5=>'five',6=>'six',
              7=>'seven',8=>'eight',9=>'nine',10=>'ten',11=>'eleven',12=>'twelve',
              18=>'eighteen',24=>'twenty-four',36=>'thirty-six',48=>'forty-eight',60=>'sixty'];
    $word = $words[$total] ?? null;
    return $word ? strtoupper($word) . ' (' . $total . ')' : (string)$total;
}

$startDate     = (string)($contract['start_date'] ?? '');
$endDate       = (string)($contract['end_date']   ?? '');
$createdAt     = (string)($contract['created_at'] ?? $startDate);
$agreementDate = contractLongDate($createdAt ?: null);
$periodText    = contractPeriodText($startDate ?: null, $endDate ?: null);
$empName       = strtoupper(trim((string)($employee['full_name'] ?? '')));
$empDesig      = strtoupper(trim((string)($employee['designation'] ?? 'EMPLOYEE')));
$contractNo    = (string)($contract['contract_number'] ?? '');
$company       = current_company() ?? [];
$companyName   = (string)($company['name'] ?? 'the Company');
$companyLogo   = company_logo_url($company);
$companyAddress= (string)($company['address'] ?? '');
$companyPhone  = (string)($company['phone'] ?? '');
$companyEmail  = (string)($company['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Employment Contract &mdash; <?= e($empName) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Times New Roman',Times,serif;font-size:12pt;line-height:1.75;color:#111;background:#e8e8e8}
.page{width:210mm;min-height:297mm;margin:60px auto 30px;background:#fff;padding:20mm 22mm 24mm;box-shadow:0 2px 20px rgba(0,0,0,.18);position:relative}
.cover-page{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;page-break-after:always}
.cover-logo{max-height:110px;max-width:260px;object-fit:contain;margin-bottom:28px}
.cover-company{font-size:20pt;font-weight:700;text-transform:uppercase;color:#153e2b;letter-spacing:.5px}
.cover-rule{width:70mm;border-top:3px solid #153e2b;margin:24px auto}
.cover-title{font-size:25pt;font-weight:700;text-transform:uppercase;letter-spacing:1px;line-height:1.25}
.cover-employee{font-size:15pt;font-weight:700;margin-top:30px;text-transform:uppercase}
.cover-meta{font-size:11pt;color:#4b5563;margin-top:10px;line-height:1.7}
.cover-content{width:100%;text-align:center}.cover-content img{max-height:110px!important;max-width:260px!important;margin-bottom:28px}
.print-footer{display:none}
.action-bar{position:fixed;top:0;left:0;right:0;background:#1a3a2a;color:#fff;display:flex;align-items:center;gap:10px;padding:9px 20px;z-index:999;font-family:Arial,sans-serif;font-size:13px}
.action-bar strong{flex:1}
.action-bar button{padding:6px 16px;border:none;border-radius:4px;cursor:pointer;font-size:13px}
.btn-print{background:#fff;color:#1a3a2a;font-weight:600}
.btn-download{background:#4caf75;color:#fff;font-weight:600}
.btn-back{background:transparent;color:#ccc;border:1px solid #555 !important}
.warning-bar{position:fixed;top:42px;left:0;right:0;background:#fff7ed;color:#9a3412;border-bottom:1px solid #fed7aa;padding:7px 20px;font-family:Arial,sans-serif;font-size:12px;z-index:998}
.letterhead{text-align:center;border-bottom:3px double #1a3a2a;padding-bottom:14px;margin-bottom:20px}
.letterhead .doc-logo{max-height:76px;max-width:160px;object-fit:contain;margin-bottom:8px}
.letterhead .company-name{font-size:16pt;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#1a3a2a}
.letterhead .doc-title{font-size:13pt;font-weight:bold;letter-spacing:2px;text-transform:uppercase;margin-top:6px}
.letterhead .dated{font-size:10pt;margin-top:4px;color:#444}
.letterhead .contract-no{font-size:9.5pt;color:#666;margin-top:2px}
.between-block{text-align:center;margin:18px 0;font-size:11pt}
.between-block .parties{font-weight:bold;font-size:12pt;margin:8px 0}
.address-block{text-align:center;font-size:10pt;color:#444;margin-bottom:18px;line-height:1.5}
.preamble{margin-bottom:16px;text-align:justify}
h2.clause{font-size:12pt;font-weight:bold;text-transform:uppercase;margin:18px 0 6px}
.page h1,.page h2,.page h3{font-family:'Times New Roman',Times,serif;font-weight:700;page-break-after:avoid}
.page h1{font-size:16pt;margin:22px 0 10px}.page h2{font-size:13pt;margin:20px 0 8px}.page h3{font-size:12pt;margin:18px 0 7px}
p{margin-bottom:8px;text-align:justify}
ul{margin:4px 0 10px 28px}
ul li{margin-bottom:3px}
ol{margin:4px 0 10px 28px}
ol li{margin-bottom:4px}
.blank{border-bottom:1px solid #333;display:inline-block;min-width:120px;text-align:left;padding:0 4px}
.blank-name{min-width:220px}
.blank-nrc{min-width:200px}
.signature-section{margin-top:30px;border-top:2px solid #1a3a2a;padding-top:16px}
.sig-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:16px}
.sig-block{padding:10px 0}
.sig-block .role{font-weight:bold;text-transform:uppercase;font-size:10pt;color:#1a3a2a;margin-bottom:12px}
.sig-line{border-bottom:1px solid #333;margin:28px 0 4px;width:80%}
.sig-name{font-weight:bold;font-size:10.5pt}
.sig-detail{font-size:10pt;color:#444;margin-top:2px}
.sig-date{margin-top:10px;font-size:10pt}
.sig-date span{border-bottom:1px solid #333;display:inline-block;min-width:130px}
.sig-stamp{border:1px dashed #aaa;width:80px;height:60px;text-align:center;font-size:8pt;color:#bbb;line-height:60px;margin-top:8px}
.witness-line{margin-top:10px;font-size:10pt}
.witness-line span{border-bottom:1px solid #333;display:inline-block;min-width:150px}
.page table{width:100%;border-collapse:collapse;margin:14px 0 20px;page-break-inside:avoid}
.page table th,.page table td{border:1px solid #64748b;padding:8px 10px;vertical-align:top;text-align:left}
.page table th{background:#f1f5f9;font-weight:700}
.page table.signature-table td{height:135px;padding:14px}
.page-break{page-break-before:always}
@media print{
    @page{size:A4;margin:15mm 18mm 22mm}
    body{background:#fff}
    .action-bar{display:none}
    .warning-bar{display:none}
    .page{width:auto;min-height:auto;margin:0;box-shadow:none;padding:0}
    .cover-page{min-height:245mm}
    .page-break{page-break-before:always}
    .print-footer{display:flex;position:fixed;left:18mm;right:18mm;bottom:7mm;border-top:1px solid #94a3b8;padding-top:4px;justify-content:space-between;gap:12px;font-size:8.5pt;color:#475569;font-family:'Times New Roman',Times,serif}
    .print-footer .page-number::after{content:'Page ' counter(page) ' of ' counter(pages)}
}
</style>
</head>
<body>

<div class="action-bar">
    <strong>Employment Contract &mdash; <?= e($empName) ?> &nbsp; <small style="opacity:.7"><?= e($contractNo) ?></small></strong>
    <button class="btn-back" onclick="window.history.back()">&#8592; Back</button>
    <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    <button class="btn-download" onclick="downloadHTML()">&#8615; Download</button>
</div>

<?php if (!empty($missingFields)): ?>
    <div class="warning-bar">
        Missing details were replaced with blank lines:
        <?= e(implode(', ', array_values($missingFields))) ?>.
    </div>
<?php endif; ?>

<div class="page cover-page">
    <?php if ($renderedCover !== null): ?>
        <?= $renderedCover ?>
    <?php else: ?>
    <?php if ($companyLogo !== ''): ?><img src="<?= e($companyLogo) ?>" alt="<?= e($companyName) ?> logo" class="cover-logo"><?php endif; ?>
    <div class="cover-company"><?= e($companyName) ?></div>
    <div class="cover-rule"></div>
    <div class="cover-title">Employment Contract</div>
    <div class="cover-employee"><?= $empName !== '' ? e($empName) : 'Employee' ?></div>
    <div class="cover-meta">
        Employee No: <?= e((string)($employee['employee_number'] ?? '-')) ?><br>
        Position: <?= e((string)($employee['designation'] ?? '-')) ?><br>
        Contract Reference: <?= e($contractNo !== '' ? $contractNo : '-') ?><br>
        Effective Date: <?= e(contractShortDate($startDate ?: null)) ?>
    </div>
    <?php endif; ?>
</div>

<div class="page">

<?php if ($renderedBody !== null): ?>
    <!-- ── Dynamic template body ── -->
    <?= $renderedBody ?>
    <?php if ($renderedSignature !== null): ?>
        <div class="signature-section"><?= $renderedSignature ?></div>
    <?php endif; ?>
<?php else: ?>
    <!-- ── Legacy hardcoded template ── -->

    <!-- ── Letterhead ── -->
    <div class="letterhead">
        <img src="<?= e($companyLogo) ?>" alt="<?= e($companyName) ?> logo" class="doc-logo">
        <div class="company-name"><?= e($companyName) ?></div>
        <div class="doc-title">Employment Agreement</div>
        <div class="dated">DATED <?= e(strtoupper(contractShortDate($createdAt ?: null))) ?></div>
        <?php if ($contractNo !== ''): ?>
            <div class="contract-no">Contract Ref: <?= e($contractNo) ?></div>
        <?php endif; ?>
    </div>

    <!-- ── Between block ── -->
    <div class="between-block">
        <div>BETWEEN</div>
        <div class="parties">THE COMPANY REPRESENTATIVE</div>
        <div>AND</div>
        <div class="parties"><?= $empName !== '' ? e($empName) : '<span class="blank blank-name">&nbsp;</span>' ?></div>
        <div style="margin-top:4px;font-size:11pt">&ldquo;EMPLOYEE&rdquo;</div>
    </div>

    <div class="address-block">
        C/O the Company, CHIWEMPALA AREA, NEAR CHATI, KALULUSHI<br>
        P.O BOX 260089, COPPERBELT PROVINCE &ndash; ZAMBIA
    </div>

    <!-- ── Preamble ── -->
    <p class="preamble">
        This Employment Agreement is made on this
        <strong><?= e($agreementDate) ?></strong>.
    </p>
    <p class="preamble">
        <strong>BETWEEN:</strong><br>
        THE COMPANY REPRESENTATIVE (hereinafter referred to as &ldquo;the Employer&rdquo; or &ldquo;The Company&rdquo;)
    </p>
    <p class="preamble">
        <strong>AND</strong><br>
        MR./MRS.&nbsp;<span class="blank blank-name"><?= $empName !== '' ? e($empName) : '&nbsp;' ?></span>&nbsp;
        NRC:&nbsp;<span class="blank blank-nrc">&nbsp;</span>
        (hereinafter referred to as &ldquo;the Employee&rdquo;)
    </p>

    <!-- ── Clause 1 ── -->
    <h2 class="clause">1. Appointment</h2>
    <p>The Employer hereby appoints the Employee as a
        <strong><?= e($empDesig) ?></strong>
        at the Company under the terms and conditions contained in this Agreement.</p>

    <!-- ── Clause 2 ── -->
    <h2 class="clause">2. Contract Period</h2>
    <?php if ($endDate !== ''): ?>
        <p>This Agreement shall run for a fixed term of
            <strong><?= e($periodText) ?> months</strong>
            commencing on <strong><?= e(contractShortDate($startDate ?: null)) ?></strong>
            and ending on <strong><?= e(contractShortDate($endDate)) ?></strong>.</p>
    <?php else: ?>
        <p>This Agreement shall commence on <strong><?= e(contractShortDate($startDate ?: null)) ?></strong>
            and shall continue on an open-ended basis until terminated in accordance with this Agreement.</p>
    <?php endif; ?>
    <p>Renewal of this Agreement shall depend upon:</p>
    <ul>
        <li>Satisfactory work performance;</li>
        <li>Professional conduct;</li>
        <li>Compliance with company policies;</li>
        <li>Operational requirements of The Company;</li>
        <li>Availability of funding;</li>
        <li>Attendance and punctuality record.</li>
    </ul>
    <p>Renewal shall not be automatic.</p>

    <!-- ── Clause 3 ── -->
    <h2 class="clause">3. Probationary Period</h2>
    <p>The Employee shall serve a probationary period of three (3) months.</p>
    <p>During probation:</p>
    <ul>
        <li>The salary shall be K3,000 per month;</li>
        <li>Performance, discipline, attendance, service quality, and professionalism shall be assessed.</li>
    </ul>
    <p>The Employer may:</p>
    <ul>
        <li>Confirm employment;</li>
        <li>Extend probation for up to three additional months; or</li>
        <li>Terminate employment upon unsatisfactory performance.</li>
    </ul>
    <p>Upon successful confirmation, salary shall increase to K4,000 per month.</p>

    <!-- ── Clause 4 ── -->
    <h2 class="clause">4. Hours of Work</h2>
    <p>The Employee shall work from:</p>
    <ul>
        <li>Monday to Friday;</li>
        <li>07:00 hours to 16:30 hours.</li>
    </ul>
    <p>The Employee may also be required to:</p>
    <ul>
        <li>Support assigned operational duties;</li>
        <li>Attend meetings;</li>
        <li>Participate in company activities;</li>
        <li>Assist with urgent work priorities where reasonable;</li>
        <li>Attend weekend activities where operationally necessary.</li>
    </ul>
    <p>The Employee acknowledges that their responsibilities may require reasonable additional hours beyond normal working hours where operationally necessary.</p>

    <!-- ── Clause 5 ── -->
    <h2 class="clause">5. Performance Expectations</h2>
    <p>The Employee shall:</p>
    <ol>
        <li>Prepare assigned work and supporting materials on time;</li>
        <li>Complete assigned duties within prescribed timelines;</li>
        <li>Maintain proper records for assigned responsibilities;</li>
        <li>Submit reports and required documentation within deadlines;</li>
        <li>Participate in improvement plans where performance gaps are identified;</li>
        <li>Maintain acceptable quality and productivity standards;</li>
        <li>Attend staff meetings and training sessions;</li>
        <li>Maintain professional conduct at work;</li>
        <li>Protect the welfare and safety of colleagues, clients, and company stakeholders.</li>
    </ol>
    <p>The Company reserves the right to conduct:</p>
    <ul>
        <li>Work reviews;</li>
        <li>Performance appraisals;</li>
        <li>Quality inspections;</li>
        <li>Service performance reviews.</li>
    </ul>
    <p>Persistent poor performance may result in counseling, performance improvement plans, written warnings, non-renewal or termination.</p>

    <div class="page-break"></div>

    <!-- ── Clause 6 ── -->
    <h2 class="clause">6. Performance Responsibility</h2>
    <p>The Employee acknowledges that reliable delivery of assigned duties is a core responsibility. Failure to complete assigned work, submit required reports, attend scheduled work, or maintain acceptable standards shall constitute poor performance.</p>
    <p>The Company may require improvement measures where performance consistently falls below expected standards.</p>

    <!-- ── Clause 7 ── -->
    <h2 class="clause">7. Attendance and Punctuality</h2>
    <p>The Employee shall:</p>
    <ul>
        <li>Report for duty on time;</li>
        <li>Sign attendance registers where applicable;</li>
        <li>Notify management promptly in case of illness or absence;</li>
        <li>Obtain approval before taking leave or being absent.</li>
    </ul>
    <p>Unauthorized absence for more than three (3) consecutive working days may be treated as job abandonment. Repeated lateness or absenteeism shall constitute misconduct.</p>

    <!-- ── Clause 8 ── -->
    <h2 class="clause">8. Remuneration</h2>
    <p>The Employee shall receive:</p>
    <ul>
        <li>K4,000 monthly basic salary after probation;</li>
        <li>Statutory deductions as required by law.</li>
    </ul>
    <p>Salary payments shall be subject to tax deductions, NAPSA contributions, and any lawful deductions authorized by law or by the Employee. The Employer reserves the right to review remuneration based on company financial position, performance, and operational requirements.</p>

    <!-- ── Clause 9 ── -->
    <h2 class="clause">9. Settling Allowance</h2>
    <p>Upon successful completion of probation and confirmation of employment, the Employee shall receive a one-time settling allowance of K1,000. The settling allowance shall not be payable if employment is terminated during probation.</p>

    <!-- ── Clause 10 ── -->
    <h2 class="clause">10. Leave</h2>
    <p>The Employee shall accrue leave at the rate provided under applicable labour laws. Leave shall be applied for formally, approved by management, and scheduled in a manner that minimizes disruption to company operations. The Company may defer leave where operationally necessary.</p>

    <!-- ── Clause 11 ── -->
    <h2 class="clause">11. Public Holidays</h2>
    <p>The Employee shall be entitled to gazetted public holidays recognized by the Republic of Zambia.</p>

    <!-- ── Clause 12 ── -->
    <h2 class="clause">12. Gratuity</h2>
    <p>The Employee shall be entitled to gratuity equivalent to 5% of annual basic salary for each completed year served. Gratuity shall only be payable upon successful completion of the contract term and where employment has not been terminated for gross misconduct.</p>

    <!-- ── Clause 13 ── -->
    <h2 class="clause">13. Maternity Leave</h2>
    <p>Maternity leave shall be granted in accordance with the laws of the Republic of Zambia.</p>

    <!-- ── Clause 14 ── -->
    <h2 class="clause">14. Confidentiality</h2>
    <p>The Employee shall not disclose confidential information relating to clients, suppliers, employees, company finances, company operations, internal reports, business materials, or company systems and records. This obligation shall continue even after termination of employment.</p>

    <!-- ── Clause 15 ── -->
    <h2 class="clause">15. Company Property</h2>
    <p>All Company Property issued to the Employee including keys, documents, laptops, work materials, electronic records, furniture, and accommodation assets shall remain property of The Company. Upon termination, all property must be returned immediately. The Company may recover the value of lost or damaged property caused through negligence or misconduct.</p>

    <!-- ── Clause 16 ── -->
    <h2 class="clause">16. Staff Accommodation</h2>
    <p>Where accommodation is provided, it shall only be for the duration of active employment and shall not create tenancy rights. The Employee shall vacate the premises within seven (7) days of termination unless otherwise approved in writing. The Employee shall maintain the accommodation in good condition.</p>

    <!-- ── Clause 17 ── -->
    <h2 class="clause">17. Professional Conduct</h2>
    <p>The Employee shall maintain professional ethics, treat colleagues, clients, and company stakeholders fairly and respectfully, avoid abusive or inappropriate conduct, and avoid conduct that damages the reputation of The Company. The Employee shall comply with company policies, lawful instructions, and applicable regulatory requirements.</p>

    <!-- ── Clause 18 ── -->
    <h2 class="clause">18. Social Media and Public Communication</h2>
    <p>The Employee shall not publish false or damaging statements about The Company, disclose confidential company information online, or use social media in a manner that brings The Company into disrepute.</p>

    <!-- ── Clause 19 ── -->
    <h2 class="clause">19. Disciplinary Action</h2>
    <p>The Employer may take disciplinary action for negligence, insubordination, poor performance, absenteeism, misconduct, theft or fraud, harassment or abuse, breach of confidentiality, or gross misconduct. Disciplinary action may include verbal warning, written warning, suspension, final warning, or termination. The Employee shall be given an opportunity to respond before disciplinary action is finalized, except in cases warranting immediate removal for safety or serious misconduct.</p>

    <!-- ── Clause 20 ── -->
    <h2 class="clause">20. Termination</h2>
    <p>Either party may terminate this Agreement by giving ninety (90) days written notice, or payment in lieu of notice where permissible. The Employer may terminate employment without notice for gross misconduct.</p>
    <p>Grounds for termination include gross misconduct, persistent poor performance, serious negligence, fraud or dishonesty, breach of confidentiality, physical or verbal abuse, repeated absenteeism, insubordination, and criminal conduct affecting suitability for employment.</p>

    <!-- ── Clause 21 ── -->
    <h2 class="clause">21. Intellectual Property</h2>
    <p>All work materials, documents, designs, computer programs, company systems, and research or innovations developed during employment using company resources shall remain the property of The Company.</p>

    <!-- ── Clause 22 ── -->
    <h2 class="clause">22. Governing Law</h2>
    <p>This Agreement shall be governed by the laws of the Republic of Zambia.</p>

    <!-- ── Clause 23 ── -->
    <h2 class="clause">23. Entire Agreement</h2>
    <p>This Agreement constitutes the entire agreement between the parties and supersedes all prior discussions or understandings. Any amendments shall only be valid if made in writing and signed by both parties.</p>

    <!-- ── Signatures ── -->
    <div class="page-break"></div>
    <div class="signature-section">
        <p style="text-align:center;font-weight:bold;font-size:11pt">IN WITNESS WHEREOF, the parties have duly executed this Agreement on the day and year first before written.</p>

        <div class="sig-grid">
            <!-- Employer -->
            <div class="sig-block">
                <div class="role">THE COMPANY REPRESENTATIVE</div>
                <p style="font-size:10pt">Signed, Sealed and Delivered by:</p>
                <div class="sig-line"></div>
                <div class="sig-name">Managing Director</div>
                <div class="sig-date">Date: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                <div class="sig-stamp">STAMP</div>
                <div class="witness-line" style="margin-top:14px">In the presence of: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                <div class="sig-date">Date: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
            </div>

            <!-- Company representative -->
            <div class="sig-block">
                <div class="role">Company Representative</div>
                <p style="font-size:10pt">Signed, Sealed and Delivered by:</p>
                <div class="sig-line"></div>
                <div class="sig-name">Company Representative</div>
                <div class="sig-detail">NRC: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Cell: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="witness-line" style="margin-top:10px">In the presence of: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                <div class="sig-date">Date: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
            </div>
        </div>

        <!-- Employee signature (full width) -->
        <div class="sig-block" style="margin-top:24px;border-top:1px solid #ccc;padding-top:14px">
            <div class="role">Employee</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px">
                <div>
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= $empName !== '' ? e($empName) : '<span class="blank blank-name">&nbsp;</span>' ?></div>
                    <div class="sig-detail">NRC: <span class="blank blank-nrc">&nbsp;</span></div>
                    <div class="sig-detail" style="margin-top:4px">Cell: <span class="blank" style="min-width:160px">&nbsp;</span></div>
                    <div class="sig-date" style="margin-top:8px">Date: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                </div>
                <div>
                    <p style="font-size:10pt;margin-bottom:4px">In the presence of:</p>
                    <div class="sig-line" style="margin-top:40px"></div>
                    <div class="sig-date">Date: <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

</div><!-- /.page -->

<div class="print-footer">
    <span><?= $renderedFooter !== null ? $renderedFooter : e($companyName) . ($companyAddress !== '' ? ' | ' . e($companyAddress) : '') . ($companyPhone !== '' ? ' | ' . e($companyPhone) : '') . ($companyEmail !== '' ? ' | ' . e($companyEmail) : '') ?></span>
    <span class="page-number"></span>
</div>

<script>
function downloadHTML() {
    const content = document.documentElement.outerHTML;
    const blob = new Blob([content], { type: 'text/html' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = <?= json_encode(($downloadName ?: 'contract') . '.html') ?>;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>

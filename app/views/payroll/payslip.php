<?php
    $earningsLines = $earningsLines ?? [];
    $deductionLines = $deductionLines ?? [];
    $salary = $salary ?? [];
    $downloadName = $downloadName ?? 'payslip';
    $companyName = $companyName ?? app_product_name();
    $companyAddress = $companyAddress ?? 'Payroll Office';
    $companyLogoUrl = $companyLogoUrl ?? company_logo_url();
    $paymentDate = (string) ($paymentDate ?? ($item['run_date'] ?? date('Y-m-d')));
    $paymentMethod = (string) ($paymentMethod ?? 'Bank Transfer');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Payslip — <?= e((string) ($item['pay_period'] ?? '')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Mono:wght@400;500&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --ink: #0f0f0f;
            --ink-muted: #6b6b6b;
            --ink-faint: #c8c8c8;
            --bg: #f5f2ed;
            --surface: #ffffff;
            --accent: #1a3a2a;
            --accent-light: #e8f0eb;
            --rule: #e2ddd6;
            --positive: #1a3a2a;
            --negative: #8b2020;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
            color: var(--ink);
        }

        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            letter-spacing: 0.01em;
            text-decoration: none;
        }

        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #0f2419; transform: translateY(-1px); box-shadow: 0 4px 16px rgba(26,58,42,.25); }

        .btn-outline { background: transparent; color: var(--accent); border: 1.5px solid var(--accent); }
        .btn-outline:hover { background: var(--accent-light); transform: translateY(-1px); }

        #payslip {
            background: var(--surface);
            width: 100%;
            max-width: 860px;
            padding: 56px 60px;
            box-shadow: 0 2px 4px rgba(0,0,0,.04), 0 12px 48px rgba(0,0,0,.08);
            position: relative;
            overflow: hidden;
        }

        #payslip::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 120px; height: 120px;
            background: var(--accent-light);
            clip-path: polygon(100% 0, 100% 100%, 0 0);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 40px;
            position: relative;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .company-name {
            font-family: 'DM Serif Display', serif;
            font-size: 28px;
            letter-spacing: -0.02em;
            color: var(--accent);
            line-height: 1;
            margin-bottom: 6px;
        }

        .company-meta {
            font-size: 12px;
            color: var(--ink-muted);
            line-height: 1.7;
        }

        .slip-label { text-align: right; }

        .slip-label .title {
            font-family: 'DM Serif Display', serif;
            font-size: 22px;
            color: var(--ink);
            letter-spacing: -0.01em;
            margin-bottom: 4px;
        }

        .slip-label .period {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--ink-muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .divider {
            height: 1px;
            background: var(--rule);
            margin: 0 0 32px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px 24px;
            background: var(--accent-light);
            padding: 24px 28px;
            margin-bottom: 36px;
            border-left: 3px solid var(--accent);
        }

        .info-item label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: 4px;
        }

        .info-item span {
            font-size: 14px;
            font-weight: 500;
            color: var(--ink);
            word-break: break-word;
        }

        .columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }

        .section-heading {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--rule);
        }

        table { width: 100%; border-collapse: collapse; }

        td {
            font-size: 13.5px;
            padding: 7px 0;
            border-bottom: 1px solid var(--rule);
            color: var(--ink);
            line-height: 1.4;
            vertical-align: top;
        }

        td:last-child {
            text-align: right;
            font-family: 'DM Mono', monospace;
            font-size: 13px;
        }

        tr:last-child td { border-bottom: none; }
        td.label { color: var(--ink-muted); font-weight: 400; }

        .subtotal td {
            padding-top: 12px;
            font-weight: 700;
            border-top: 1.5px solid var(--ink-faint);
            border-bottom: none;
        }

        .subtotal td:last-child { color: var(--positive); }
        .deductions .subtotal td:last-child { color: var(--negative); }

        .net-pay {
            background: var(--accent);
            color: #fff;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
        }

        .net-pay .label {
            font-size: 13px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.75;
            margin-bottom: 4px;
        }

        .net-pay .amount {
            font-family: 'DM Serif Display', serif;
            font-size: 38px;
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .net-pay .payment-info { text-align: right; }
        .net-pay .bank { font-size: 13px; opacity: 0.75; margin-bottom: 4px; }
        .net-pay .account { font-family: 'DM Mono', monospace; font-size: 14px; letter-spacing: 0.05em; }

        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: var(--rule);
            border: 1px solid var(--rule);
            margin-bottom: 36px;
        }

        .summary-cell {
            background: var(--surface);
            padding: 16px 18px;
            text-align: center;
        }

        .summary-cell .s-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: 6px;
        }

        .summary-cell .s-value {
            font-family: 'DM Mono', monospace;
            font-size: 18px;
            font-weight: 500;
            color: var(--ink);
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid var(--rule);
            padding-top: 20px;
            gap: 24px;
        }

        .footer-note {
            font-size: 11px;
            color: var(--ink-muted);
            line-height: 1.7;
            max-width: 430px;
        }

        .signature-block { text-align: right; }

        .sig-line {
            width: 180px;
            border-bottom: 1.5px solid var(--ink);
            margin-bottom: 6px;
            margin-left: auto;
        }

        .sig-label { font-size: 11px; color: var(--ink-muted); }

        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            #payslip {
                box-shadow: none;
                padding: 32px 40px;
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            body { padding: 16px; }
            #payslip { padding: 28px 22px; }
            .header, .net-pay, .footer { flex-direction: column; align-items: flex-start; }
            .slip-label { text-align: left; }
            .net-pay .payment-info, .signature-block { text-align: left; }
            .summary-row, .columns, .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn btn-outline" href="<?= e(base_url('payroll/edit/' . (string) ($item['payroll_run_id'] ?? 0))) ?>">Back to Run</a>
        <button class="btn btn-primary" type="button" onclick="window.print()">Print Payslip</button>
        <button class="btn btn-outline" type="button" onclick="downloadHTML()">Download HTML</button>
    </div>

    <div id="payslip">
        <div class="header">
            <div class="brand">
                <img src="<?= e((string) $companyLogoUrl) ?>" alt="<?= e((string) $companyName) ?>">
                <div>
                    <div class="company-name"><?= e((string) $companyName) ?></div>
                    <div class="company-meta"><?= e((string) $companyAddress) ?><br>
                        Employee No: <?= e((string) ($item['employee_number'] ?? '-')) ?> · Department: <?= e((string) ($item['department_name'] ?? '-')) ?></div>
                </div>
            </div>
            <div class="slip-label">
                <div class="title">Payslip</div>
                <div class="period"><?= e((string) ($item['pay_period'] ?? '')) ?></div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="info-grid">
            <div class="info-item">
                <label>Employee Name</label>
                <span><?= e((string) ($item['employee_name'] ?? '')) ?></span>
            </div>
            <div class="info-item">
                <label>Employee Number</label>
                <span><?= e((string) ($item['employee_number'] ?? '')) ?></span>
            </div>
            <div class="info-item">
                <label>Department</label>
                <span><?= e((string) ($item['department_name'] ?? '-')) ?></span>
            </div>
            <div class="info-item">
                <label>Job Title</label>
                <span><?= e((string) ($item['designation'] ?? '-')) ?></span>
            </div>
            <div class="info-item">
                <label>Payment Date</label>
                <span><?= e((string) $paymentDate) ?></span>
            </div>
            <div class="info-item">
                <label>Payment Method</label>
                <span><?= e((string) $paymentMethod) ?></span>
            </div>
        </div>

        <div class="columns">
            <div class="earnings">
                <div class="section-heading">Earnings</div>
                <table>
                    <?php foreach ($earningsLines as $line): ?>
                        <tr>
                            <td class="label"><?= e((string) ($line['label'] ?? '')) ?></td>
                            <td><?= e(format_currency((float) ($line['amount'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal">
                        <td>Gross Earnings</td>
                        <td><?= e(format_currency((float) $grossEarnings)) ?></td>
                    </tr>
                </table>
            </div>

            <div class="deductions">
                <div class="section-heading">Deductions</div>
                <table>
                    <?php if (empty($deductionLines)): ?>
                        <tr>
                            <td class="label">No active deductions</td>
                            <td><?= e(format_currency(0)) ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deductionLines as $line): ?>
                            <tr>
                                <td class="label"><?= e((string) ($line['label'] ?? '')) ?></td>
                                <td><?= e(format_currency((float) ($line['amount'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="subtotal">
                        <td>Total Deductions</td>
                        <td><?= e(format_currency((float) $totalDeductions)) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="net-pay">
            <div>
                <div class="label">Net Pay</div>
                <div class="amount"><?= e(format_currency((float) $netPay)) ?></div>
            </div>
            <div class="payment-info">
                <div class="bank"><?= e((string) ($item['bank_name'] ?: 'Bank Transfer')) ?></div>
                <div class="account"><?= e((string) ($item['bank_account_number'] ?: 'Pending bank details')) ?></div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-note">
                This is a computer-generated payslip and does not require a physical signature.
            </div>
            <div class="signature-block">
                <div class="sig-line"></div>
                <div class="sig-label">Authorised Signatory</div>
            </div>
        </div>
    </div>

    <script>
        function downloadHTML() {
            const content = document.documentElement.outerHTML;
            const blob = new Blob([content], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = <?= json_encode(($downloadName ?: 'payslip') . '.html') ?>;
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

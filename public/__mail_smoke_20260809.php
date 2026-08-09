<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';
const SMOKE_TOKEN = 'corevia-mail-20260809-7b52d00';

if (($_GET['token'] ?? '') !== SMOKE_TOKEN) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

spl_autoload_register(static function (string $className): void {
    $paths = [
        BASE_PATH . '/app/controllers/' . $className . '.php',
        BASE_PATH . '/app/models/' . $className . '.php',
    ];

    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require BASE_PATH . '/helpers/common.php';
require BASE_PATH . '/config/app.php';
require BASE_PATH . '/core/MailService.php';

$mailSettings = corevia_default_mail_settings();
$serverFile = BASE_PATH . '/config/server.php';
if (is_file($serverFile)) {
    $serverConfig = require $serverFile;
    $serverMail = is_array($serverConfig) ? ($serverConfig['mail'] ?? []) : [];
    if (is_array($serverMail)) {
        foreach ($serverMail as $key => $value) {
            if ($value !== null && (string) $value !== '') {
                $mailSettings[$key] = (string) $value;
            }
        }
    }
}

$recipients = [
    'estherkalumba40@gmail.com',
    'mwala.libati94@gmail.com',
    'emmanuel.libati@gmail.com',
];

$samples = [
    'company_admin_account' => [
        'subject' => 'Corevia test - company administrator access',
        'title' => 'Welcome to Corevia HR & Payroll',
        'intro' => 'Your company administrator account is ready',
        'body' => '<p>Hello Demo Administrator,</p><p>Your administrator account for <strong>Demo Company Limited</strong> has been created.</p>'
            . '<table style="border-collapse:collapse;width:100%;margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0">'
            . '<tr><td style="padding:10px 12px;font-weight:bold;width:170px">Login email</td><td style="padding:10px 12px">demo.admin@example.com</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">One-time password</td><td style="padding:10px 12px;font-family:Consolas,monospace">DemoTemp@2026</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">Login link</td><td style="padding:10px 12px"><a href="' . e(public_url('auth/login')) . '">' . e(public_url('auth/login')) . '</a></td></tr>'
            . '</table><p>The system will ask the user to create a private password on first login.</p>',
    ],
    'employee_portal_account' => [
        'subject' => 'Corevia test - employee portal access',
        'title' => 'Employee Portal Access',
        'intro' => 'Your employee self-service account is ready',
        'body' => '<p>Hello Demo Employee,</p><p>Your employee portal account has been created.</p>'
            . '<table style="border-collapse:collapse;width:100%;margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0">'
            . '<tr><td style="padding:10px 12px;font-weight:bold;width:170px">Employee number</td><td style="padding:10px 12px">EMP-DEMO</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">One-time password</td><td style="padding:10px 12px;font-family:Consolas,monospace">EmployeeTemp@2026</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">Portal link</td><td style="padding:10px 12px"><a href="' . e(public_url('portal/login')) . '">' . e(public_url('portal/login')) . '</a></td></tr>'
            . '</table><p>The employee must change this password on first login.</p>',
    ],
    'affiliate_account' => [
        'subject' => 'Corevia test - affiliate account access',
        'title' => 'Welcome to Corevia Affiliates',
        'intro' => 'Your affiliate account is ready',
        'body' => '<p>Hello Demo Affiliate,</p><p>Your Corevia Affiliates account has been created.</p>'
            . '<table style="border-collapse:collapse;width:100%;margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0">'
            . '<tr><td style="padding:10px 12px;font-weight:bold;width:170px">Affiliate code</td><td style="padding:10px 12px">AFF-DEMO</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">Login email</td><td style="padding:10px 12px">affiliate@example.com</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">One-time password</td><td style="padding:10px 12px;font-family:Consolas,monospace">AffiliateTemp@2026</td></tr>'
            . '<tr><td style="padding:10px 12px;font-weight:bold">Login link</td><td style="padding:10px 12px"><a href="' . e(public_url('affiliate/auth/login')) . '">' . e(public_url('affiliate/auth/login')) . '</a></td></tr>'
            . '</table><p>The affiliate must change this password on first login.</p>',
    ],
    'contract_available' => [
        'subject' => 'Corevia test - contract available in portal',
        'title' => 'Contract Available',
        'intro' => 'Official HR document notification',
        'body' => '<p>Hello Demo Employee,</p><p>Your employment contract is available in the employee portal.</p><p>For confidentiality and security, contract documents are not sent by email.</p><p><a href="' . e(public_url('portal/contracts')) . '">Log in to view your contract</a></p>',
    ],
    'payslip_available' => [
        'subject' => 'Corevia test - payslip available in portal',
        'title' => 'Payslip Available',
        'intro' => 'Official payroll notification',
        'body' => '<p>Hello Demo Employee,</p><p>Your payslip is available in the employee portal.</p><p>For confidentiality and security, payslip documents are not sent by email.</p><p><a href="' . e(public_url('portal/payslips')) . '">Log in to view your payslip</a></p>',
    ],
    'affiliate_statement' => [
        'subject' => 'Corevia test - affiliate statement available',
        'title' => 'Affiliate Statement Available',
        'intro' => 'Your affiliate payout statement is ready',
        'body' => '<p>Hello Demo Affiliate,</p><p>Your affiliate statement has been prepared. Please log in to your Corevia Affiliates portal to view the statement and payout details.</p><p><a href="' . e(public_url('affiliate/auth/login')) . '">Open affiliate portal</a></p>',
    ],
];

header('Content-Type: text/plain; charset=UTF-8');
echo 'FROM ' . ($mailSettings['smtp_from_email'] ?? '') . PHP_EOL;

$sent = 0;
$failed = 0;
foreach ($samples as $key => $sample) {
    foreach ($recipients as $to) {
        $mailer = new MailService($mailSettings);
        $ok = $mailer->send($to, '', (string) $sample['subject'], corevia_email_shell((string) $sample['title'], (string) $sample['intro'], (string) $sample['body']));
        echo ($ok ? 'SENT ' : 'FAILED ') . $key . ' -> ' . $to . ($ok ? '' : ' :: ' . $mailer->lastError()) . PHP_EOL;
        $ok ? $sent++ : $failed++;
    }
}

echo 'SUMMARY sent=' . $sent . ' failed=' . $failed . PHP_EOL;

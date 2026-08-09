<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';
const SMOKE_TOKEN = 'corevia-mail-mobile-20260809-3537975';

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

$settings = corevia_default_mail_settings();
$serverFile = BASE_PATH . '/config/server.php';
if (is_file($serverFile)) {
    $serverConfig = require $serverFile;
    $serverMail = is_array($serverConfig) ? ($serverConfig['mail'] ?? []) : [];
    if (is_array($serverMail)) {
        foreach ($serverMail as $key => $value) {
            if ($value !== null && (string) $value !== '') {
                $settings[$key] = (string) $value;
            }
        }
    }
}

$recipients = [
    'estherkalumba40@gmail.com',
    'mwala.libati94@gmail.com',
    'emmanuel.libati@gmail.com',
];

$login = e(public_url('auth/login'));
$portal = e(public_url('portal/login'));
$affiliate = e(public_url('affiliate/auth/login'));

$detailTable = static function (array $rows): string {
    $html = '<table style="border-collapse:collapse;width:100%;margin:18px 0;background:#f8fafc;border:1px solid #e2e8f0">';
    foreach ($rows as $label => $value) {
        $html .= '<tr><td style="padding:10px 12px;font-weight:bold;width:170px">' . e((string) $label) . '</td>'
            . '<td style="padding:10px 12px">' . $value . '</td></tr>';
    }
    return $html . '</table>';
};

$notifications = [
    'company_welcome' => [
        'subject' => 'Corevia test - company administrator account',
        'title' => 'Welcome to Corevia HR & Payroll',
        'intro' => 'Your company administrator account is ready',
        'body' => '<p>Hello Demo Administrator,</p><p>Your administrator account for <strong>Demo Company Limited</strong> has been created.</p>'
            . $detailTable([
                'Login email' => 'demo.admin@example.com',
                'One-time password' => '<span style="font-family:Consolas,monospace">DemoTemp@2026</span>',
                'Login link' => '<a href="' . $login . '">' . $login . '</a>',
            ])
            . '<p>The system will ask you to create a private password on first login.</p>',
    ],
    'employee_portal_access' => [
        'subject' => 'Corevia test - employee portal account',
        'title' => 'Employee Portal Access',
        'intro' => 'Your employee self-service account is ready',
        'body' => '<p>Hello Demo Employee,</p><p>Your employee portal account has been created.</p>'
            . $detailTable([
                'Employee number' => 'EMP-DEMO',
                'One-time password' => '<span style="font-family:Consolas,monospace">EmployeeTemp@2026</span>',
                'Portal link' => '<a href="' . $portal . '">' . $portal . '</a>',
            ])
            . '<p>This temporary password must be changed when you first sign in.</p>',
    ],
    'affiliate_account' => [
        'subject' => 'Corevia test - affiliate account',
        'title' => 'Welcome to Corevia Affiliates',
        'intro' => 'Your affiliate account is ready',
        'body' => '<p>Hello Demo Affiliate,</p><p>Your Corevia Affiliates account has been created.</p>'
            . $detailTable([
                'Affiliate code' => 'AFF-DEMO',
                'Login email' => 'affiliate@example.com',
                'One-time password' => '<span style="font-family:Consolas,monospace">AffiliateTemp@2026</span>',
                'Login link' => '<a href="' . $affiliate . '">' . $affiliate . '</a>',
            ])
            . '<p>This temporary password must be changed when you first sign in.</p>',
    ],
    'contract_available' => [
        'subject' => 'Corevia test - contract available',
        'title' => 'Contract Available',
        'intro' => 'Official HR document notification',
        'body' => '<p>Hello Demo Employee,</p><p>Your employment contract is available in the employee portal.</p><p>For confidentiality and security, contract documents are not sent by email.</p><p><a href="' . $portal . '">Log in to view your contract</a></p>',
    ],
    'payslip_available' => [
        'subject' => 'Corevia test - payslip available',
        'title' => 'Payslip Available',
        'intro' => 'Official payroll notification',
        'body' => '<p>Hello Demo Employee,</p><p>Your payslip is available in the employee portal.</p><p>For confidentiality and security, payslip documents are not sent by email.</p><p><a href="' . $portal . '">Log in to view your payslip</a></p>',
    ],
    'contract_expiry' => [
        'subject' => 'Corevia test - contract expiry reminder',
        'title' => 'Contract Expiry Reminder',
        'intro' => 'Employee contract action required',
        'body' => '<p>Hello Demo Employee,</p><p>Your contract is approaching its expiry date. Please log in to your employee portal or contact HR for renewal guidance.</p><p><a href="' . $portal . '">Open employee portal</a></p>',
    ],
    'contract_expired' => [
        'subject' => 'Corevia test - contract expired',
        'title' => 'Contract Expired',
        'intro' => 'Employee contract status notification',
        'body' => '<p>Hello Demo Employee,</p><p>Your contract has reached its recorded end date. Please log in to your employee portal or contact HR for the next action.</p><p><a href="' . $portal . '">Open employee portal</a></p>',
    ],
    'contract_renewed' => [
        'subject' => 'Corevia test - contract renewed',
        'title' => 'Contract Renewed',
        'intro' => 'Employee contract update',
        'body' => '<p>Hello Demo Employee,</p><p>Your renewed contract is available in the employee portal. Please log in to review the updated details.</p><p><a href="' . $portal . '">Open employee portal</a></p>',
    ],
    'affiliate_statement' => [
        'subject' => 'Corevia test - affiliate statement',
        'title' => 'Affiliate Statement Available',
        'intro' => 'Your affiliate payout statement is ready',
        'body' => '<p>Hello Demo Affiliate,</p><p>Your affiliate statement has been prepared. Please log in to your Corevia Affiliates portal to view statement and payout details.</p><p><a href="' . $affiliate . '">Open affiliate portal</a></p>',
    ],
    'subscription_invoice' => [
        'subject' => 'Corevia test - subscription invoice',
        'title' => 'Subscription Invoice Available',
        'intro' => 'Corevia billing notification',
        'body' => '<p>Hello Demo Client,</p><p>Your Corevia subscription invoice is available. Please log in to the administrator portal or contact StoneSoft for billing assistance.</p><p><a href="' . $login . '">Open administrator portal</a></p>',
    ],
    'smtp_test' => [
        'subject' => 'Corevia test - SMTP/settings email',
        'title' => 'Email Settings Test',
        'intro' => 'Corevia email delivery check',
        'body' => '<p>This confirms a Corevia email notification test from the production environment.</p>',
    ],
];

header('Content-Type: text/plain; charset=UTF-8');
echo 'FROM ' . ($settings['smtp_from_email'] ?? '') . PHP_EOL;
echo 'LOGO ' . corevia_brand_logo_url() . PHP_EOL;

$sent = 0;
$failed = 0;
foreach ($notifications as $key => $notification) {
    foreach ($recipients as $recipient) {
        $mailer = new MailService($settings);
        $html = corevia_email_shell((string) $notification['title'], (string) $notification['intro'], (string) $notification['body']);
        $ok = $mailer->send($recipient, '', (string) $notification['subject'], $html);
        echo ($ok ? 'SENT ' : 'FAILED ') . $key . ' -> ' . $recipient . ($ok ? '' : ' :: ' . $mailer->lastError()) . PHP_EOL;
        $ok ? $sent++ : $failed++;
    }
}

echo 'SUMMARY sent=' . $sent . ' failed=' . $failed . PHP_EOL;

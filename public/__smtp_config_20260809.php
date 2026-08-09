<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';
const SETUP_TOKEN = 'corevia-smtp-setup-20260809-ce71d98';

if (($_POST['token'] ?? $_GET['token'] ?? '') !== SETUP_TOKEN) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
});

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
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/SecretBox.php';
require BASE_PATH . '/core/MailService.php';

$password = trim((string) ($_POST['smtp_password'] ?? ''));
if ($password === '') {
    http_response_code(400);
    echo 'Missing smtp password';
    exit;
}

$settings = [
    'email_notifications_enabled' => '1',
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => '465',
    'smtp_encryption' => 'ssl',
    'smtp_username' => 'corevia@stonesoftzambia.com',
    'smtp_password' => SecretBox::encrypt($password),
    'smtp_from_email' => 'corevia@stonesoftzambia.com',
    'smtp_from_name' => 'Corevia HR & Payroll',
    'smtp_hr_email' => 'corevia@stonesoftzambia.com',
];

$stmt = db()->prepare(
    'INSERT INTO settings (company_id, setting_key, setting_value)
     VALUES (0, :setting_key, :setting_value)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
);

foreach ($settings as $key => $value) {
    $stmt->execute(['setting_key' => $key, 'setting_value' => $value]);
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
        'subject' => 'Corevia SMTP test - company administrator account',
        'title' => 'Welcome to Corevia HR & Payroll',
        'intro' => 'Your company administrator account is ready',
        'body' => '<p>Hello Demo Administrator,</p><p>Your administrator account for <strong>Demo Company Limited</strong> has been created.</p>'
            . $detailTable([
                'Login email' => 'demo.admin@example.com',
                'One-time password' => '<span style="font-family:Consolas,monospace">DemoTemp@2026</span>',
                'Login link' => '<a href="' . $login . '">' . $login . '</a>',
            ]),
    ],
    'employee_portal_access' => [
        'subject' => 'Corevia SMTP test - employee portal account',
        'title' => 'Employee Portal Access',
        'intro' => 'Your employee self-service account is ready',
        'body' => '<p>Hello Demo Employee,</p><p>Your employee portal account has been created.</p>'
            . $detailTable([
                'Employee number' => 'EMP-DEMO',
                'One-time password' => '<span style="font-family:Consolas,monospace">EmployeeTemp@2026</span>',
                'Portal link' => '<a href="' . $portal . '">' . $portal . '</a>',
            ]),
    ],
    'affiliate_account' => [
        'subject' => 'Corevia SMTP test - affiliate account',
        'title' => 'Welcome to Corevia Affiliates',
        'intro' => 'Your affiliate account is ready',
        'body' => '<p>Hello Demo Affiliate,</p><p>Your Corevia Affiliates account has been created.</p>'
            . $detailTable([
                'Affiliate code' => 'AFF-DEMO',
                'Login email' => 'affiliate@example.com',
                'One-time password' => '<span style="font-family:Consolas,monospace">AffiliateTemp@2026</span>',
                'Login link' => '<a href="' . $affiliate . '">' . $affiliate . '</a>',
            ]),
    ],
    'contract_available' => ['subject' => 'Corevia SMTP test - contract available', 'title' => 'Contract Available', 'intro' => 'Official HR document notification', 'body' => '<p>Your employment contract is available in the employee portal.</p><p><a href="' . $portal . '">Log in to view your contract</a></p>'],
    'payslip_available' => ['subject' => 'Corevia SMTP test - payslip available', 'title' => 'Payslip Available', 'intro' => 'Official payroll notification', 'body' => '<p>Your payslip is available in the employee portal.</p><p><a href="' . $portal . '">Log in to view your payslip</a></p>'],
    'contract_expiry' => ['subject' => 'Corevia SMTP test - contract expiry reminder', 'title' => 'Contract Expiry Reminder', 'intro' => 'Employee contract action required', 'body' => '<p>Your contract is approaching expiry. Please log in or contact HR.</p><p><a href="' . $portal . '">Open employee portal</a></p>'],
    'contract_expired' => ['subject' => 'Corevia SMTP test - contract expired', 'title' => 'Contract Expired', 'intro' => 'Employee contract status notification', 'body' => '<p>Your contract has reached its recorded end date. Please log in or contact HR for next steps.</p><p><a href="' . $portal . '">Open employee portal</a></p>'],
    'contract_renewed' => ['subject' => 'Corevia SMTP test - contract renewed', 'title' => 'Contract Renewed', 'intro' => 'Employee contract update', 'body' => '<p>Your renewed contract is available in the employee portal.</p><p><a href="' . $portal . '">Open employee portal</a></p>'],
    'affiliate_statement' => ['subject' => 'Corevia SMTP test - affiliate statement', 'title' => 'Affiliate Statement Available', 'intro' => 'Your affiliate payout statement is ready', 'body' => '<p>Your affiliate statement has been prepared. Please log in to your Corevia Affiliates portal.</p><p><a href="' . $affiliate . '">Open affiliate portal</a></p>'],
    'subscription_invoice' => ['subject' => 'Corevia SMTP test - subscription invoice', 'title' => 'Subscription Invoice Available', 'intro' => 'Corevia billing notification', 'body' => '<p>Your Corevia subscription invoice is available. Please log in or contact StoneSoft for billing support.</p><p><a href="' . $login . '">Open administrator portal</a></p>'],
    'smtp_test' => ['subject' => 'Corevia SMTP test - settings email', 'title' => 'Email Settings Test', 'intro' => 'Corevia email delivery check', 'body' => '<p>This confirms a Corevia SMTP notification test from production.</p>'],
];

header('Content-Type: text/plain; charset=UTF-8');
echo 'SMTP configured for corevia@stonesoftzambia.com' . PHP_EOL;
echo 'LOGO ' . corevia_brand_logo_url() . PHP_EOL;

$mailSettings = corevia_mail_settings(0);
$sent = 0;
$failed = 0;
foreach ($notifications as $key => $notification) {
    foreach ($recipients as $recipient) {
        $mailer = new MailService($mailSettings);
        $html = corevia_email_shell((string) $notification['title'], (string) $notification['intro'], (string) $notification['body']);
        $ok = $mailer->send($recipient, '', (string) $notification['subject'], $html);
        echo ($ok ? 'SENT ' : 'FAILED ') . $key . ' -> ' . $recipient . ($ok ? '' : ' :: ' . $mailer->lastError()) . PHP_EOL;
        $ok ? $sent++ : $failed++;
    }
}

echo 'SUMMARY sent=' . $sent . ' failed=' . $failed . PHP_EOL;

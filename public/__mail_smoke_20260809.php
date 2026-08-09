<?php

declare(strict_types=1);

const SMOKE_TOKEN = '1bc81c7f66d441a2ad9e35d8203973f0';

if (!hash_equals(SMOKE_TOKEN, (string) ($_GET['token'] ?? ''))) {
    http_response_code(404);
    exit('Not found');
}

const BASE_PATH = __DIR__ . '/..';

require BASE_PATH . '/helpers/common.php';
require BASE_PATH . '/config/app.php';
require BASE_PATH . '/core/MailService.php';

header('Content-Type: text/plain; charset=UTF-8');

$serverConfig = is_file(BASE_PATH . '/config/server.php') ? require BASE_PATH . '/config/server.php' : [];
$mailSettings = is_array($serverConfig) && isset($serverConfig['mail']) && is_array($serverConfig['mail'])
    ? $serverConfig['mail']
    : [];

$mailSettings = array_merge([
    'email_notifications_enabled' => '1',
    'smtp_from_email' => 'info@stonesoftzambia.com',
    'smtp_from_name' => app_vendor_name(),
], $mailSettings);

$recipients = [
    'estherkalumba40@gmail.com' => 'Esther Kalumba',
    'mwala.libati94@gmail.com' => 'Mwala Libati',
    'emmanuel.libati@gmail.com' => 'Emmanuel Libati',
];

$notifications = [
    ['company_welcome', 'Company Account Created', 'Your company administrator account has been created. Please log in to Corevia to complete first sign-in and company setup.', public_url('auth/login')],
    ['employee_portal_access', 'Employee Portal Access', 'Your employee self-service portal access is ready. Please sign in to view your HR information and complete required actions.', public_url('portal/login')],
    ['contract_available', 'Contract Available', 'Your employment contract is now available for review in the employee portal.', public_url('portal/contract')],
    ['payslip_available', 'Payslip Available', 'Your payslip is now available in the employee portal.', public_url('portal/payslips')],
    ['contract_expiry', 'Contract Expiry Reminder', 'A contract requires attention before its expiry date. Please log in to review the contract record and take appropriate action.', public_url('portal/contract')],
    ['contract_expired', 'Contract Expired', 'A contract has reached its expiry date. Please log in to Corevia to review the contract record.', public_url('portal/contract')],
    ['contract_renewed', 'Contract Renewed', 'A renewed contract is now recorded in Corevia. Please log in to view the updated details.', public_url('portal/contract')],
    ['affiliate_statement', 'Affiliate Statement Available', 'Your affiliate statement is available in the Corevia Affiliate Portal.', public_url('affiliate/dashboard/statement')],
    ['subscription_invoice', 'Subscription Invoice Available', 'A subscription invoice has been generated. Please log in to Corevia to view invoice and payment status.', public_url('auth/login')],
    ['smtp_test', 'Email Configuration Check', 'This confirms that the configured production mail path can deliver Corevia notifications.', public_url('auth/login')],
];

function smoke_html(string $title, string $name, string $body, string $url): string
{
    $safeTitle = e($title);
    $safeName = e($name);
    $safeBody = nl2br(e($body));
    $safeUrl = e($url);
    $sentAt = e(date('Y-m-d H:i:s'));

    return <<<HTML
    <html><body style="margin:0;background:#f3f6fb;font-family:Arial,sans-serif;color:#0f172a">
    <div style="max-width:660px;margin:0 auto;padding:28px 18px">
      <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
        <div style="background:#0f172a;color:#fff;padding:22px 26px">
          <h1 style="font-size:22px;line-height:1.3;margin:0">TEST: {$safeTitle}</h1>
          <p style="margin:8px 0 0;color:#cbd5e1">Secure Corevia portal notification test</p>
        </div>
        <div style="padding:26px;line-height:1.6">
          <p style="margin-top:0">Hello {$safeName},</p>
          <p>{$safeBody}</p>
          <p>For confidentiality and security, documents and statements are not sent by email. Please log in to the relevant Corevia portal to view the latest information.</p>
          <p style="margin:24px 0"><a href="{$safeUrl}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold">Open Corevia Portal</a></p>
          <p style="font-size:13px;color:#64748b">If the button does not open, copy this link into your browser:<br>{$safeUrl}</p>
          <p style="font-size:12px;color:#94a3b8">Smoke test sent at {$sentAt}</p>
          <p style="margin-bottom:0">Kind regards,<br>StoneSoft IT Solutions</p>
        </div>
      </div>
    </div>
    </body></html>
    HTML;
}

$mailer = new MailService($mailSettings);
$sent = 0;
$failed = 0;

foreach ($notifications as [$key, $title, $body, $url]) {
    foreach ($recipients as $email => $name) {
        $ok = $mailer->send($email, $name, 'TEST: Corevia - ' . $title, smoke_html($title, $name, $body, $url));
        echo ($ok ? 'SENT' : 'FAILED') . " {$key} -> {$email}";
        if (!$ok) {
            echo ' | ' . $mailer->lastError();
            $failed++;
        } else {
            $sent++;
        }
        echo PHP_EOL;
    }
}

echo "SUMMARY sent={$sent} failed={$failed}" . PHP_EOL;

<?php

/**
 * Contract notification cron script.
 *
 * Run this daily via Windows Task Scheduler:
 *   Program : C:\xampp\php\php.exe
 *   Arguments: C:\xampp\htdocs\LibosecMs\public\cron_notify.php
 *   Start in : C:\xampp\htdocs\LibosecMs\public
 *
 * Or via XAMPP shell / CLI:
 *   php C:\xampp\htdocs\LibosecMs\public\cron_notify.php
 *
 * This script auto-expires contracts and sends all pending
 * contract notification emails (expiring soon / expired / renewed).
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// ── Bootstrap ──────────────────────────────────────────────────────────────
require BASE_PATH . '/config/app.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Model.php';
require BASE_PATH . '/core/SecretBox.php';
require BASE_PATH . '/core/MailService.php';

// Auto-load models needed
foreach (['EmployeeContract', 'ContractNotification'] as $cls) {
    require BASE_PATH . '/app/models/' . $cls . '.php';
}

// ── Run ────────────────────────────────────────────────────────────────────
$started = date('Y-m-d H:i:s');
echo "[{$started}] Starting contract notification run...\n";

try {
    $contractModel = new EmployeeContract();
    $contractModel->autoExpire();
    echo "[{$started}] Auto-expire check completed.\n";

    $notifier = new ContractNotification();
    $result   = $notifier->dispatchAll();

    echo "[{$started}] Sent: {$result['sent']} | Skipped: {$result['skipped']}\n";

    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $err) {
            echo "[{$started}] ERROR: {$err}\n";
        }
    }
} catch (Throwable $e) {
    echo "[{$started}] FATAL: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[{$started}] Done.\n";
exit(0);

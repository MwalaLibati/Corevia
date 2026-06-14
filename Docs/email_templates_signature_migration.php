<?php

declare(strict_types=1);

/**
 * Corevia migration: company email templates, document attachment toggles,
 * and company email signature defaults.
 *
 * Safe to run more than once.
 * It preserves existing settings and only inserts missing defaults.
 */

require_once __DIR__ . '/../config/database.php';

$db = db();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function migration_column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_NAME = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function migration_index_exists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND INDEX_NAME = :index'
    );
    $stmt->execute(['table' => $table, 'index' => $index]);
    return (int) $stmt->fetchColumn() > 0;
}

function migration_single_column_unique_indexes(PDO $db, string $table, string $column): array
{
    $stmt = $db->prepare(
        'SELECT INDEX_NAME
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND NON_UNIQUE = 0
         GROUP BY INDEX_NAME
         HAVING COUNT(*) = 1
            AND MAX(COLUMN_NAME = :column) = 1'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);
    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$messages = [];

$db->exec(
    "CREATE TABLE IF NOT EXISTS settings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id BIGINT UNSIGNED NULL,
        setting_key VARCHAR(150) NOT NULL,
        setting_value TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_settings_company_id (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

if (!migration_column_exists($db, 'settings', 'company_id')) {
    $db->exec('ALTER TABLE settings ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER id');
    $messages[] = 'Added settings.company_id.';
}

$companyIds = $db->query('SELECT id FROM companies ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
if ($companyIds === []) {
    throw new RuntimeException('No companies found. Create at least one company before running this migration.');
}

$fallbackCompanyId = (int) $companyIds[0];
$db->prepare('UPDATE settings SET company_id = :company_id WHERE company_id IS NULL')
    ->execute(['company_id' => $fallbackCompanyId]);

foreach (migration_single_column_unique_indexes($db, 'settings', 'setting_key') as $indexName) {
    if ($indexName !== 'PRIMARY') {
        $db->exec('ALTER TABLE settings DROP INDEX `' . str_replace('`', '``', $indexName) . '`');
        $messages[] = "Dropped old single-company unique index {$indexName}.";
    }
}

if (!migration_index_exists($db, 'settings', 'uq_settings_company_key')) {
    $db->exec('ALTER TABLE settings ADD UNIQUE KEY uq_settings_company_key (company_id, setting_key)');
    $messages[] = 'Added unique key uq_settings_company_key.';
}

if (migration_column_exists($db, 'settings', 'company_id')) {
    $db->exec('ALTER TABLE settings MODIFY COLUMN company_id BIGINT UNSIGNED NOT NULL');
}

$defaults = [
    'email_template_contract_subject' => 'Employment Contract - {{employee_name}}',
    'email_template_contract_body' => "Dear {{employee_name}},\n\nPlease find your employment contract attached for your review and records.\n\nContract number: {{contract_number}}\nContract type: {{contract_type}}",
    'email_template_contract_attach_document' => '1',
    'email_template_payslip_subject' => 'Payslip for {{pay_period}} - {{employee_name}}',
    'email_template_payslip_body' => "Dear {{employee_name}},\n\nYour payslip for {{pay_period}} is ready. Please find the attached payslip document for your records.\n\nNet pay: {{net_pay}}.",
    'email_template_payslip_attach_document' => '1',
    'email_signature_body' => "Regards,\n{{company_name}}\n{{company_phone}}\n{{company_email}}",
];

$exists = $db->prepare(
    'SELECT id FROM settings
     WHERE company_id = :company_id AND setting_key = :setting_key
     LIMIT 1'
);
$insert = $db->prepare(
    'INSERT INTO settings (company_id, setting_key, setting_value)
     VALUES (:company_id, :setting_key, :setting_value)'
);

$inserted = 0;
foreach ($companyIds as $companyId) {
    foreach ($defaults as $key => $value) {
        $exists->execute(['company_id' => (int) $companyId, 'setting_key' => $key]);
        if ($exists->fetchColumn()) {
            continue;
        }

        $insert->execute([
            'company_id' => (int) $companyId,
            'setting_key' => $key,
            'setting_value' => $value,
        ]);
        $inserted++;
    }
}

$messages[] = "Seeded {$inserted} missing email template setting(s).";

echo "Corevia email template migration completed.\n";
foreach ($messages as $message) {
    echo '- ' . $message . "\n";
}

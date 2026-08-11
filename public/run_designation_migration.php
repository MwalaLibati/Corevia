<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';

require BASE_PATH . '/config/app.php';
require BASE_PATH . '/config/database.php';

$token = (string) ($_GET['token'] ?? '');
$expected = hash_hmac('sha256', 'designation_migration_2026_08_11', defined('APP_SECRET') ? (string) APP_SECRET : 'corevia');

if (!hash_equals($expected, $token)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

function migration_table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function migration_column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function migration_index_exists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
    );
    $stmt->execute(['table' => $table, 'index_name' => $index]);

    return (int) $stmt->fetchColumn() > 0;
}

$db = db();
$messages = [];

try {
    $db->exec(
        "CREATE TABLE IF NOT EXISTS designations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            department_id BIGINT UNSIGNED NULL,
            code VARCHAR(30) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $messages[] = 'designations table ready';

    $designationColumns = [
        'company_id' => 'BIGINT UNSIGNED NOT NULL',
        'department_id' => 'BIGINT UNSIGNED NULL',
        'code' => 'VARCHAR(30) NOT NULL',
        'name' => 'VARCHAR(150) NOT NULL',
        'description' => 'TEXT NULL',
        'is_active' => 'TINYINT(1) NOT NULL DEFAULT 1',
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ];

    foreach ($designationColumns as $column => $definition) {
        if (!migration_column_exists($db, 'designations', $column)) {
            $db->exec("ALTER TABLE designations ADD COLUMN {$column} {$definition}");
            $messages[] = "added designations.{$column}";
        }
    }

    if (!migration_index_exists($db, 'designations', 'uq_designations_company_code')) {
        $db->exec('ALTER TABLE designations ADD UNIQUE KEY uq_designations_company_code (company_id, code)');
        $messages[] = 'added designation code index';
    }
    if (!migration_index_exists($db, 'designations', 'uq_designations_company_name')) {
        $db->exec('ALTER TABLE designations ADD UNIQUE KEY uq_designations_company_name (company_id, name)');
        $messages[] = 'added designation name index';
    }
    if (!migration_index_exists($db, 'designations', 'idx_designations_department')) {
        $db->exec('ALTER TABLE designations ADD KEY idx_designations_department (department_id)');
        $messages[] = 'added designation department index';
    }

    if (!migration_column_exists($db, 'employees', 'designation_id')) {
        $db->exec('ALTER TABLE employees ADD COLUMN designation_id BIGINT UNSIGNED NULL AFTER department_id');
        $messages[] = 'added employees.designation_id';
    }

    if (
        migration_table_exists($db, 'employee_onboarding_requests')
        && migration_column_exists($db, 'employee_onboarding_requests', 'designation')
        && !migration_column_exists($db, 'employee_onboarding_requests', 'designation_id')
    ) {
        $db->exec('ALTER TABLE employee_onboarding_requests ADD COLUMN designation_id BIGINT UNSIGNED NULL AFTER department_id');
        $messages[] = 'added employee_onboarding_requests.designation_id';
    }

    $companies = $db->query('SELECT id FROM companies')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $companyId) {
        $companyId = (int) $companyId;
        $stmt = $db->prepare(
            "SELECT DISTINCT TRIM(designation) AS designation_name
             FROM employees
             WHERE company_id = :cid
               AND designation IS NOT NULL
               AND TRIM(designation) <> ''"
        );
        $stmt->execute(['cid' => $companyId]);

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $exists = $db->prepare('SELECT id FROM designations WHERE company_id = :cid AND LOWER(name) = LOWER(:name) LIMIT 1');
            $exists->execute(['cid' => $companyId, 'name' => $name]);
            if ($exists->fetchColumn()) {
                continue;
            }

            $lastStmt = $db->prepare("SELECT code FROM designations WHERE company_id = :cid AND code REGEXP '^DES[0-9]{3,}$' ORDER BY code DESC LIMIT 1");
            $lastStmt->execute(['cid' => $companyId]);
            $last = (string) ($lastStmt->fetchColumn() ?: '');
            $next = $last !== '' ? ((int) substr($last, 3)) + 1 : 1;
            $code = 'DES' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);

            $insert = $db->prepare(
                "INSERT INTO designations (company_id, code, name, description, is_active)
                 VALUES (:cid, :code, :name, 'Seeded from existing employee records.', 1)"
            );
            $insert->execute(['cid' => $companyId, 'code' => $code, 'name' => $name]);
        }

        $map = $db->prepare(
            "UPDATE employees e
             JOIN designations des
               ON des.company_id = e.company_id
              AND LOWER(des.name) = LOWER(TRIM(e.designation))
             SET e.designation_id = des.id
             WHERE e.company_id = :cid
               AND e.designation_id IS NULL
               AND e.designation IS NOT NULL
               AND TRIM(e.designation) <> ''"
        );
        $map->execute(['cid' => $companyId]);
    }

    $messages[] = 'seeded and mapped existing employee designations';
    echo "OK\n" . implode("\n", $messages) . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
}

<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$db = db();

function onboardingTableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!onboardingTableExists($db, 'employee_onboarding_requests')) {
    $db->exec(
        "CREATE TABLE employee_onboarding_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            status ENUM('Draft','Sent','Opened','Submitted','Approved','Expired','Cancelled') NOT NULL DEFAULT 'Draft',
            invited_full_name VARCHAR(150) NOT NULL,
            invited_email VARCHAR(150) NULL,
            invited_phone VARCHAR(30) NULL,
            department_id BIGINT UNSIGNED NULL,
            designation VARCHAR(120) NULL,
            employment_type VARCHAR(100) NOT NULL DEFAULT 'Permanent',
            expected_start_date DATE NULL,
            expires_at DATETIME NOT NULL,
            submitted_at DATETIME NULL,
            approved_at DATETIME NULL,
            approved_by BIGINT UNSIGNED NULL,
            created_by BIGINT UNSIGNED NULL,
            created_employee_id BIGINT UNSIGNED NULL,
            full_name VARCHAR(150) NULL,
            email VARCHAR(150) NULL,
            phone VARCHAR(30) NULL,
            nrc_number VARCHAR(30) NULL,
            date_of_birth DATE NULL,
            gender VARCHAR(30) NULL,
            address TEXT NULL,
            napsa_number VARCHAR(50) NULL,
            tpin VARCHAR(30) NULL,
            nhima_number VARCHAR(50) NULL,
            bank_name VARCHAR(120) NULL,
            bank_account_number VARCHAR(60) NULL,
            next_of_kin_name VARCHAR(150) NULL,
            next_of_kin_phone VARCHAR(30) NULL,
            next_of_kin_relationship VARCHAR(80) NULL,
            notes TEXT NULL,
            hr_notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_onboarding_company_status (company_id, status),
            INDEX idx_onboarding_token (token),
            CONSTRAINT fk_onboarding_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            CONSTRAINT fk_onboarding_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
            CONSTRAINT fk_onboarding_employee FOREIGN KEY (created_employee_id) REFERENCES employees(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "Created employee_onboarding_requests\n";
}

if (!onboardingTableExists($db, 'employee_onboarding_documents')) {
    $db->exec(
        "CREATE TABLE employee_onboarding_documents (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            onboarding_request_id BIGINT UNSIGNED NOT NULL,
            company_id BIGINT UNSIGNED NOT NULL,
            document_type VARCHAR(80) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_onboarding_docs_request (onboarding_request_id),
            CONSTRAINT fk_onboarding_docs_request FOREIGN KEY (onboarding_request_id) REFERENCES employee_onboarding_requests(id) ON DELETE CASCADE,
            CONSTRAINT fk_onboarding_docs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "Created employee_onboarding_documents\n";
}

try {
    $moduleKey = 'onboarding';
    $plans = $db->query('SELECT id FROM subscription_plans')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($plans as $planId) {
        $insertPlan = $db->prepare(
            'INSERT IGNORE INTO subscription_plan_modules (plan_id, module_key) VALUES (:plan_id, :module_key)'
        );
        $insertPlan->execute(['plan_id' => (int) $planId, 'module_key' => $moduleKey]);
    }

    $stmt = $db->query("SELECT id, name FROM roles WHERE name IN ('Super Admin','HR Officer')");
    foreach ($stmt->fetchAll() as $role) {
        $insert = $db->prepare(
            'INSERT IGNORE INTO role_module_permissions (role_id, module_key) VALUES (:role_id, :module_key)'
        );
        $insert->execute(['role_id' => (int) $role['id'], 'module_key' => $moduleKey]);
    }
    echo "Granted onboarding module to subscription plans, Super Admin, and HR Officer roles\n";
} catch (Throwable $e) {
    echo "Role permission seed skipped: {$e->getMessage()}\n";
}

echo "Employee onboarding capture migration completed.\n";

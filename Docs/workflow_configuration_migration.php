<?php

declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$db = db();

function workflowCfgTableExists(PDO $db, string $table): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!workflowCfgTableExists($db, 'workflow_definitions')) {
    $db->exec(
        "CREATE TABLE workflow_definitions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            company_id BIGINT UNSIGNED NOT NULL,
            workflow_type VARCHAR(80) NOT NULL,
            name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_workflow_company_type (company_id, workflow_type),
            KEY idx_workflow_def_company (company_id),
            CONSTRAINT fk_workflow_def_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "Created workflow_definitions\n";
}

if (!workflowCfgTableExists($db, 'workflow_steps')) {
    $db->exec(
        "CREATE TABLE workflow_steps (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            workflow_definition_id BIGINT UNSIGNED NOT NULL,
            step_order INT UNSIGNED NOT NULL,
            step_name VARCHAR(150) NOT NULL,
            required_role VARCHAR(120) NOT NULL,
            action_label VARCHAR(120) NOT NULL DEFAULT 'Approve',
            is_final TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_workflow_step_order (workflow_definition_id, step_order),
            KEY idx_workflow_steps_definition (workflow_definition_id),
            CONSTRAINT fk_workflow_steps_definition FOREIGN KEY (workflow_definition_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "Created workflow_steps\n";
}

$defaults = [
    'employee_onboarding' => [
        'name' => 'Employee Onboarding',
        'description' => 'Approval flow before submitted onboarding data becomes an employee profile.',
        'steps' => [
            ['HR Review', 'HR Officer', 'Approve & Create Employee', 1],
        ],
    ],
    'payroll' => [
        'name' => 'Payroll Run Approval',
        'description' => 'Default payroll review path before posting and releasing payslips.',
        'steps' => [
            ['HR Review', 'HR Officer', 'Submit for Finance Review', 0],
            ['Finance Review', 'Finance Officer', 'Submit for Director Approval', 0],
            ['Director Approval', 'Super Admin', 'Approve Payroll', 1],
        ],
    ],
    'leave' => [
        'name' => 'Leave Approval',
        'description' => 'Default leave approval path.',
        'steps' => [
            ['HR Approval', 'HR Officer', 'Approve Leave', 1],
        ],
    ],
    'contract' => [
        'name' => 'Contract Approval',
        'description' => 'Default contract approval path.',
        'steps' => [
            ['HR Review', 'HR Officer', 'Submit for Admin Approval', 0],
            ['Admin Approval', 'Super Admin', 'Approve Contract', 1],
        ],
    ],
    'salary_change' => [
        'name' => 'Salary Change Approval',
        'description' => 'Default salary change approval path.',
        'steps' => [
            ['Finance Review', 'Finance Officer', 'Submit for Admin Approval', 0],
            ['Admin Approval', 'Super Admin', 'Approve Salary Change', 1],
        ],
    ],
];

$companies = $db->query('SELECT id FROM companies')->fetchAll(PDO::FETCH_COLUMN);
foreach ($companies as $companyId) {
    foreach ($defaults as $type => $definition) {
        $stmt = $db->prepare(
            'INSERT IGNORE INTO workflow_definitions (company_id, workflow_type, name, description)
             VALUES (:company_id, :workflow_type, :name, :description)'
        );
        $stmt->execute([
            'company_id' => (int) $companyId,
            'workflow_type' => $type,
            'name' => $definition['name'],
            'description' => $definition['description'],
        ]);

        $definitionIdStmt = $db->prepare(
            'SELECT id FROM workflow_definitions WHERE company_id = :company_id AND workflow_type = :workflow_type LIMIT 1'
        );
        $definitionIdStmt->execute(['company_id' => (int) $companyId, 'workflow_type' => $type]);
        $definitionId = (int) $definitionIdStmt->fetchColumn();
        if ($definitionId <= 0) {
            continue;
        }

        $countStmt = $db->prepare('SELECT COUNT(*) FROM workflow_steps WHERE workflow_definition_id = :id');
        $countStmt->execute(['id' => $definitionId]);
        if ((int) $countStmt->fetchColumn() > 0) {
            continue;
        }

        foreach ($definition['steps'] as $index => $step) {
            $insertStep = $db->prepare(
                'INSERT INTO workflow_steps (workflow_definition_id, step_order, step_name, required_role, action_label, is_final)
                 VALUES (:definition_id, :step_order, :step_name, :required_role, :action_label, :is_final)'
            );
            $insertStep->execute([
                'definition_id' => $definitionId,
                'step_order' => $index + 1,
                'step_name' => $step[0],
                'required_role' => $step[1],
                'action_label' => $step[2],
                'is_final' => (int) $step[3],
            ]);
        }
    }
}

try {
    foreach ($db->query('SELECT id FROM subscription_plans')->fetchAll(PDO::FETCH_COLUMN) as $planId) {
        $stmt = $db->prepare('INSERT IGNORE INTO subscription_plan_modules (plan_id, module_key) VALUES (:plan_id, :module_key)');
        $stmt->execute(['plan_id' => (int) $planId, 'module_key' => 'workflow_settings']);
    }

    $stmt = $db->query("SELECT id FROM roles WHERE name IN ('Super Admin')");
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $roleId) {
        $insert = $db->prepare('INSERT IGNORE INTO role_module_permissions (role_id, module_key) VALUES (:role_id, :module_key)');
        $insert->execute(['role_id' => (int) $roleId, 'module_key' => 'workflow_settings']);
    }
    echo "Granted workflow settings module\n";
} catch (Throwable $e) {
    echo "Workflow module permission seed skipped: {$e->getMessage()}\n";
}

echo "Workflow configuration migration completed.\n";

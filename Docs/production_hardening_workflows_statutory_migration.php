<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$db = db();

$db->exec(
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        login_area VARCHAR(40) NOT NULL,
        identifier VARCHAR(190) NOT NULL,
        ip_address VARCHAR(64) NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_login_attempts_lookup (login_area, identifier, ip_address, success, attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$db->exec(
    "CREATE TABLE IF NOT EXISTS statutory_payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id BIGINT UNSIGNED NOT NULL,
        payroll_run_id BIGINT UNSIGNED NOT NULL,
        pay_period VARCHAR(20) NOT NULL,
        statutory_code VARCHAR(20) NOT NULL,
        employee_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        employer_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
        payment_reference VARCHAR(120) NULL,
        payment_date DATE NULL,
        status ENUM('Pending','Paid','Partially Paid','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
        notes TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_statutory_payment_run_code (company_id, payroll_run_id, statutory_code),
        KEY idx_statutory_payments_company_period (company_id, pay_period, statutory_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$types = [
    'salary_advance' => ['Salary Advance Approval', 'Controls employee and finance-created salary advance approvals.'],
    'employee_termination' => ['Employee Termination Approval', 'Controls employee termination/deactivation approvals.'],
];

$companyIds = $db->query('SELECT id FROM companies ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
foreach ($companyIds as $companyId) {
    foreach ($types as $type => [$name, $description]) {
        $stmt = $db->prepare('SELECT id FROM workflow_definitions WHERE company_id = :cid AND workflow_type = :type LIMIT 1');
        $stmt->execute(['cid' => $companyId, 'type' => $type]);
        $definitionId = (int) $stmt->fetchColumn();

        if ($definitionId <= 0) {
            $insert = $db->prepare(
                'INSERT INTO workflow_definitions (company_id, workflow_type, name, description, is_active)
                 VALUES (:cid, :type, :name, :description, 1)'
            );
            $insert->execute(['cid' => $companyId, 'type' => $type, 'name' => $name, 'description' => $description]);
            $definitionId = (int) $db->lastInsertId();
        }

        $count = $db->prepare('SELECT COUNT(*) FROM workflow_steps WHERE workflow_definition_id = :id');
        $count->execute(['id' => $definitionId]);
        if ((int) $count->fetchColumn() > 0) {
            continue;
        }

        $steps = $type === 'salary_advance'
            ? [
                [1, 'Finance Review', 'Finance Officer', 'Approve Advance', 1],
            ]
            : [
                [1, 'HR Review', 'HR Officer', 'Review Termination', 0],
                [2, 'Admin Approval', 'Super Admin', 'Approve Termination', 1],
            ];

        $stepInsert = $db->prepare(
            'INSERT INTO workflow_steps (workflow_definition_id, step_order, step_name, required_role, action_label, is_final)
             VALUES (:definition_id, :step_order, :step_name, :required_role, :action_label, :is_final)'
        );
        foreach ($steps as $step) {
            $stepInsert->execute([
                'definition_id' => $definitionId,
                'step_order' => $step[0],
                'step_name' => $step[1],
                'required_role' => $step[2],
                'action_label' => $step[3],
                'is_final' => $step[4],
            ]);
        }
    }
}

echo "Production hardening/workflow/statutory migration completed.\n";

<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/app/models/CompanyEmployeeIdentity.php';

$migration = new CompanyEmployeeIdentity(db());
$summary = $migration->migrateExistingEmployeeNumbers();

echo "Company employee identity migration completed.\n";
foreach ($summary as $company) {
    echo sprintf(
        "[%d] %s: %s (%d employee(s))\n",
        $company['company_id'],
        $company['company_name'],
        $company['employee_code'],
        $company['employees']
    );
}

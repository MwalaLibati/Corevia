<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/..';

require BASE_PATH . '/config/app.php';
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/core/Model.php';
require BASE_PATH . '/core/Tenant.php';
require BASE_PATH . '/app/models/MonthlyAttendanceSummary.php';
require BASE_PATH . '/app/models/AttendancePayrollRule.php';

Tenant::resolve();
(new MonthlyAttendanceSummary())->ensureSchema();
(new AttendancePayrollRule())->ensureSchema();

echo "Monthly attendance summary schema is ready.\n";

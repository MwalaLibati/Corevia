-- Fix missing Salary Structures nav visibility for databases using the legacy key.
-- Run this on deployed databases where Salary Structures is missing from the sidebar.

INSERT IGNORE INTO role_module_permissions (role_id, module_key)
SELECT DISTINCT role_id, 'salary'
FROM role_module_permissions
WHERE module_key IN ('salary_structures', 'payroll', 'bonuses', 'deductions', 'salary_advances');

INSERT IGNORE INTO subscription_plan_modules (plan_id, module_key)
SELECT DISTINCT plan_id, 'salary'
FROM subscription_plan_modules
WHERE module_key IN ('salary_structures', 'payroll', 'bonuses', 'deductions', 'salary_advances');


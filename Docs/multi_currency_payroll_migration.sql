-- Multi-currency employee payroll support.
-- Run this once per Corevia database before using employee-level payroll currencies in production.

ALTER TABLE employee_salary
    ADD COLUMN IF NOT EXISTS currency_code VARCHAR(3) NOT NULL DEFAULT 'ZMW',
    ADD COLUMN IF NOT EXISTS exchange_rate_to_company DECIMAL(18,8) NOT NULL DEFAULT 1.00000000;

ALTER TABLE salary_change_requests
    ADD COLUMN IF NOT EXISTS currency_code VARCHAR(3) NOT NULL DEFAULT 'ZMW',
    ADD COLUMN IF NOT EXISTS exchange_rate_to_company DECIMAL(18,8) NOT NULL DEFAULT 1.00000000;

ALTER TABLE payroll_items
    ADD COLUMN IF NOT EXISTS currency_code VARCHAR(3) NOT NULL DEFAULT 'ZMW',
    ADD COLUMN IF NOT EXISTS exchange_rate_to_company DECIMAL(18,8) NOT NULL DEFAULT 1.00000000,
    ADD COLUMN IF NOT EXISTS gross_pay_company DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS total_deductions_company DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS net_pay_company DECIMAL(14,2) NOT NULL DEFAULT 0.00;

UPDATE employee_salary
SET currency_code = 'ZMW'
WHERE currency_code IS NULL OR currency_code = '';

UPDATE salary_change_requests
SET currency_code = 'ZMW'
WHERE currency_code IS NULL OR currency_code = '';

UPDATE payroll_items
SET currency_code = 'ZMW',
    exchange_rate_to_company = 1.00000000,
    gross_pay_company = gross_pay,
    total_deductions_company = total_deductions,
    net_pay_company = net_pay
WHERE currency_code IS NULL
   OR currency_code = ''
   OR gross_pay_company = 0.00;

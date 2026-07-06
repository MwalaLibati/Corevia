-- Corevia dynamic allowance engine
-- Safe to run more than once on MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS allowance_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL,
    calculation_type VARCHAR(20) NOT NULL DEFAULT 'Fixed',
    default_value DECIMAL(14,2) NOT NULL DEFAULT 0,
    is_taxable TINYINT(1) NOT NULL DEFAULT 1,
    included_in_gross TINYINT(1) NOT NULL DEFAULT 1,
    included_in_napsa TINYINT(1) NOT NULL DEFAULT 0,
    included_in_nhima TINYINT(1) NOT NULL DEFAULT 1,
    is_recurring TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_allowance_company_code (company_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salary_structure_allowances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    salary_structure_id BIGINT UNSIGNED NOT NULL,
    allowance_type_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    effective_from DATE NULL,
    effective_to DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_structure_allowance (salary_structure_id, allowance_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_allowance_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    allowance_type_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NULL,
    is_excluded TINYINT(1) NOT NULL DEFAULT 0,
    effective_from DATE NULL,
    effective_to DATE NULL,
    reason TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_employee_allowance_override (company_id, employee_id, allowance_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payroll_item_earnings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    payroll_run_id BIGINT UNSIGNED NOT NULL,
    payroll_item_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    earning_code VARCHAR(30) NOT NULL,
    earning_name VARCHAR(120) NOT NULL,
    earning_category VARCHAR(30) NOT NULL,
    calculation_type VARCHAR(30) NULL,
    calculation_base DECIMAL(14,2) NOT NULL DEFAULT 0,
    rate_percent DECIMAL(8,4) NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payroll_item_earnings_item (payroll_item_id),
    KEY idx_payroll_item_earnings_run (company_id, payroll_run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO allowance_types (company_id, name, code, calculation_type, default_value)
SELECT id, 'Housing Allowance', 'HOUSE', 'Fixed', 0 FROM companies
ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO allowance_types (company_id, name, code, calculation_type, default_value)
SELECT id, 'Transport Allowance', 'TRANS', 'Fixed', 0 FROM companies
ON DUPLICATE KEY UPDATE name = VALUES(name);
INSERT INTO allowance_types (company_id, name, code, calculation_type, default_value)
SELECT id, 'Other Allowance', 'OTHER', 'Fixed', 0 FROM companies
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT IGNORE INTO salary_structure_allowances (company_id, salary_structure_id, allowance_type_id, amount)
SELECT ss.company_id, ss.id, at.id, ss.housing_allowance
FROM salary_structures ss JOIN allowance_types at ON at.company_id = ss.company_id AND at.code = 'HOUSE'
WHERE ss.housing_allowance > 0;
INSERT IGNORE INTO salary_structure_allowances (company_id, salary_structure_id, allowance_type_id, amount)
SELECT ss.company_id, ss.id, at.id, ss.transport_allowance
FROM salary_structures ss JOIN allowance_types at ON at.company_id = ss.company_id AND at.code = 'TRANS'
WHERE ss.transport_allowance > 0;
INSERT IGNORE INTO salary_structure_allowances (company_id, salary_structure_id, allowance_type_id, amount)
SELECT ss.company_id, ss.id, at.id, ss.other_allowances
FROM salary_structures ss JOIN allowance_types at ON at.company_id = ss.company_id AND at.code = 'OTHER'
WHERE ss.other_allowances > 0;

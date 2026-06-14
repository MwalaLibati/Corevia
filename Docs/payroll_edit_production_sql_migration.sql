ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS locked_at DATETIME NULL AFTER is_locked;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS locked_by BIGINT UNSIGNED NULL AFTER locked_at;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS reversed_at DATETIME NULL AFTER locked_by;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS reversed_by BIGINT UNSIGNED NULL AFTER reversed_at;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS reversal_reason TEXT NULL AFTER reversed_by;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS correction_of_run_id BIGINT UNSIGNED NULL AFTER reversal_reason;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS tax_year_id BIGINT UNSIGNED NULL AFTER correction_of_run_id;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS payslips_released TINYINT(1) NOT NULL DEFAULT 0 AFTER tax_year_id;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS payslips_released_at DATETIME NULL AFTER payslips_released;
ALTER TABLE payroll_runs ADD COLUMN IF NOT EXISTS payslips_released_by BIGINT UNSIGNED NULL AFTER payslips_released_at;

CREATE TABLE IF NOT EXISTS payroll_run_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payroll_run_id BIGINT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(50) NULL,
  reference_number VARCHAR(100) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payroll_run_payments_run (payroll_run_id),
  INDEX idx_payroll_run_payments_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_item_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payroll_item_id BIGINT UNSIGNED NOT NULL,
  payroll_run_payment_id BIGINT UNSIGNED NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(50) NULL,
  reference_number VARCHAR(100) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pip_item (payroll_item_id),
  KEY idx_pip_run_payment (payroll_run_payment_id),
  KEY idx_pip_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_tax_years (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  starts_on DATE NOT NULL,
  ends_on DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_payroll_tax_years_company (company_id),
  KEY idx_payroll_tax_years_dates (starts_on, ends_on),
  UNIQUE KEY uq_payroll_tax_year_company_name (company_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_calculation_audit (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  payroll_run_id BIGINT UNSIGNED NOT NULL,
  payroll_item_id BIGINT UNSIGNED NULL,
  employee_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  gross_pay DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total_deductions DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  net_pay DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  snapshot_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pca_company_run (company_id, payroll_run_id),
  KEY idx_pca_item (payroll_item_id),
  KEY idx_pca_employee (employee_id),
  KEY idx_pca_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_run_reversals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  payroll_run_id BIGINT UNSIGNED NOT NULL,
  correction_run_id BIGINT UNSIGNED NULL,
  reason TEXT NOT NULL,
  reversed_by BIGINT UNSIGNED NULL,
  reversed_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prr_company_run (company_id, payroll_run_id),
  KEY idx_prr_correction (correction_run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO payroll_tax_years (company_id, name, starts_on, ends_on, is_active, notes)
SELECT c.id, CONCAT('Tax Year ', YEAR(CURDATE())), CONCAT(YEAR(CURDATE()), '-01-01'), CONCAT(YEAR(CURDATE()), '-12-31'), 1, 'Default tax year created during production deployment.'
FROM companies c
WHERE NOT EXISTS (
  SELECT 1
  FROM payroll_tax_years pty
  WHERE pty.company_id = c.id
    AND pty.name = CONCAT('Tax Year ', YEAR(CURDATE()))
);

UPDATE payroll_runs
SET is_locked = 1,
    locked_at = COALESCE(locked_at, NOW())
WHERE status IN ('Posted','Partially Paid','Paid');

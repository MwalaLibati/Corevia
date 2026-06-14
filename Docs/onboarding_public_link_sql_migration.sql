CREATE TABLE IF NOT EXISTS employee_onboarding_requests (
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
  INDEX idx_onboarding_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS company_id BIGINT UNSIGNED NOT NULL AFTER id;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS token VARCHAR(128) NOT NULL AFTER company_id;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS status ENUM('Draft','Sent','Opened','Submitted','Approved','Expired','Cancelled') NOT NULL DEFAULT 'Draft' AFTER token;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS invited_full_name VARCHAR(150) NOT NULL AFTER status;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS invited_email VARCHAR(150) NULL AFTER invited_full_name;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS invited_phone VARCHAR(30) NULL AFTER invited_email;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS department_id BIGINT UNSIGNED NULL AFTER invited_phone;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS designation VARCHAR(120) NULL AFTER department_id;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS employment_type VARCHAR(100) NOT NULL DEFAULT 'Permanent' AFTER designation;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS expected_start_date DATE NULL AFTER employment_type;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER expected_start_date;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS submitted_at DATETIME NULL AFTER expires_at;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER submitted_at;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS approved_by BIGINT UNSIGNED NULL AFTER approved_at;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS created_by BIGINT UNSIGNED NULL AFTER approved_by;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS created_employee_id BIGINT UNSIGNED NULL AFTER created_by;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NULL AFTER created_employee_id;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER full_name;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS nrc_number VARCHAR(30) NULL AFTER phone;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER nrc_number;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS gender VARCHAR(30) NULL AFTER date_of_birth;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER gender;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS napsa_number VARCHAR(50) NULL AFTER address;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS tpin VARCHAR(30) NULL AFTER napsa_number;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS nhima_number VARCHAR(50) NULL AFTER tpin;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS bank_name VARCHAR(120) NULL AFTER nhima_number;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(60) NULL AFTER bank_name;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS next_of_kin_name VARCHAR(150) NULL AFTER bank_account_number;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS next_of_kin_phone VARCHAR(30) NULL AFTER next_of_kin_name;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS next_of_kin_relationship VARCHAR(80) NULL AFTER next_of_kin_phone;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER next_of_kin_relationship;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS hr_notes TEXT NULL AFTER notes;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER hr_notes;
ALTER TABLE employee_onboarding_requests ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

CREATE TABLE IF NOT EXISTS employee_onboarding_documents (
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
  INDEX idx_onboarding_docs_company (company_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS onboarding_request_id BIGINT UNSIGNED NOT NULL AFTER id;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS company_id BIGINT UNSIGNED NOT NULL AFTER onboarding_request_id;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS document_type VARCHAR(80) NOT NULL DEFAULT 'Supporting Document' AFTER company_id;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) NOT NULL AFTER document_type;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS stored_path VARCHAR(255) NOT NULL AFTER original_name;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS mime_type VARCHAR(120) NULL AFTER stored_path;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS file_size BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER mime_type;
ALTER TABLE employee_onboarding_documents ADD COLUMN IF NOT EXISTS uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER file_size;

INSERT IGNORE INTO role_module_permissions (role_id, module_key)
SELECT id, 'onboarding'
FROM roles
WHERE name IN ('Super Admin','Admin','Administrator','Company Admin','Company Administrator','HR Officer')
   OR access_level IN ('Super Admin','Admin','Administrator','Company Admin','Company Administrator','HR Officer');

INSERT IGNORE INTO subscription_plan_modules (plan_id, module_key)
SELECT id, 'onboarding'
FROM subscription_plans
WHERE is_active = 1;

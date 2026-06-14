CREATE TABLE IF NOT EXISTS affiliates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_code VARCHAR(30) NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(60) NULL,
  nrc_number VARCHAR(80) NULL,
  tpin VARCHAR(80) NULL,
  address TEXT NULL,
  bank_name VARCHAR(160) NULL,
  bank_account_name VARCHAR(160) NULL,
  bank_account_number VARCHAR(80) NULL,
  mobile_money_number VARCHAR(80) NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  payout_method VARCHAR(80) NULL,
  payout_details TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_affiliates_code (affiliate_code),
  UNIQUE KEY uq_affiliates_email (email),
  KEY idx_affiliates_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE affiliates
  ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash;

ALTER TABLE affiliates
  ADD COLUMN IF NOT EXISTS nrc_number VARCHAR(80) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS tpin VARCHAR(80) NULL AFTER nrc_number,
  ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER tpin,
  ADD COLUMN IF NOT EXISTS affiliate_type ENUM('Individual','Company','Consultant','Reseller','Agency') NOT NULL DEFAULT 'Individual' AFTER affiliate_code,
  ADD COLUMN IF NOT EXISTS trading_name VARCHAR(190) NULL AFTER full_name,
  ADD COLUMN IF NOT EXISTS alternate_email VARCHAR(190) NULL AFTER email,
  ADD COLUMN IF NOT EXISTS alternate_phone VARCHAR(80) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER address,
  ADD COLUMN IF NOT EXISTS province VARCHAR(120) NULL AFTER city,
  ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER province,
  ADD COLUMN IF NOT EXISTS kyc_status ENUM('Draft','Pending Review','Approved','Rejected') NOT NULL DEFAULT 'Draft' AFTER mobile_money_number,
  ADD COLUMN IF NOT EXISTS kyc_reviewed_by BIGINT UNSIGNED NULL AFTER kyc_status,
  ADD COLUMN IF NOT EXISTS kyc_reviewed_at DATETIME NULL AFTER kyc_reviewed_by,
  ADD COLUMN IF NOT EXISTS kyc_rejection_reason TEXT NULL AFTER kyc_reviewed_at,
  ADD COLUMN IF NOT EXISTS commission_basis ENUM('Paid Amount','Invoice Amount','Net Amount') NOT NULL DEFAULT 'Paid Amount' AFTER commission_rate,
  ADD COLUMN IF NOT EXISTS commission_duration ENUM('First Year','Lifetime','Fixed Months') NOT NULL DEFAULT 'First Year' AFTER commission_basis,
  ADD COLUMN IF NOT EXISTS commission_months INT NULL AFTER commission_duration,
  ADD COLUMN IF NOT EXISTS one_off_bonus DECIMAL(14,2) NOT NULL DEFAULT 0.00 AFTER commission_months,
  ADD COLUMN IF NOT EXISTS payout_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER one_off_bonus,
  ADD COLUMN IF NOT EXISTS bank_name VARCHAR(160) NULL AFTER payout_details,
  ADD COLUMN IF NOT EXISTS bank_account_name VARCHAR(160) NULL AFTER bank_name,
  ADD COLUMN IF NOT EXISTS bank_account_number VARCHAR(80) NULL AFTER bank_account_name,
  ADD COLUMN IF NOT EXISTS mobile_money_number VARCHAR(80) NULL AFTER bank_account_number;

CREATE TABLE IF NOT EXISTS affiliate_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  document_type VARCHAR(80) NOT NULL DEFAULT 'Other',
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_size BIGINT UNSIGNED NULL,
  mime_type VARCHAR(120) NULL,
  notes TEXT NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_affiliate_documents_affiliate (affiliate_id),
  KEY idx_affiliate_documents_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  company_name VARCHAR(190) NOT NULL,
  contact_person VARCHAR(160) NULL,
  contact_email VARCHAR(190) NULL,
  contact_phone VARCHAR(80) NULL,
  industry VARCHAR(120) NULL,
  employee_count INT NULL,
  estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  stage ENUM('New','Contacted','Demo Scheduled','Negotiating','Won','Lost') NOT NULL DEFAULT 'New',
  source VARCHAR(120) NULL,
  notes TEXT NULL,
  next_follow_up DATE NULL,
  converted_company_id BIGINT UNSIGNED NULL,
  converted_at DATETIME NULL,
  converted_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_affiliate_leads_affiliate (affiliate_id),
  KEY idx_affiliate_leads_stage (stage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_referrals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  referral_status ENUM('Prospect','Trial','Active','Suspended','Cancelled') NOT NULL DEFAULT 'Active',
  commission_rate DECIMAL(5,2) NULL,
  referred_at DATE NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_affiliate_referrals_company (company_id),
  KEY idx_affiliate_referrals_affiliate (affiliate_id),
  KEY idx_affiliate_referrals_status (referral_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_commissions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  referral_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NOT NULL,
  payment_id BIGINT UNSIGNED NOT NULL,
  payment_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  commission_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  commission_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  currency VARCHAR(10) NOT NULL DEFAULT 'ZMW',
  earned_at DATE NOT NULL,
  status ENUM('Pending','Approved','Paid','Reversed') NOT NULL DEFAULT 'Pending',
  paid_at DATE NULL,
  payout_reference VARCHAR(120) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_affiliate_commissions_payment (payment_id),
  KEY idx_affiliate_commissions_affiliate_status (affiliate_id, status),
  KEY idx_affiliate_commissions_company (company_id),
  KEY idx_affiliate_commissions_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE affiliate_commissions
  ADD COLUMN IF NOT EXISTS eligible_at DATE NULL AFTER earned_at,
  ADD COLUMN IF NOT EXISTS reversed_at DATETIME NULL AFTER paid_at,
  ADD COLUMN IF NOT EXISTS reversal_reason TEXT NULL AFTER reversed_at,
  ADD COLUMN IF NOT EXISTS adjustment_reason TEXT NULL AFTER notes;

CREATE TABLE IF NOT EXISTS payment_methods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO payment_methods (code, name, sort_order) VALUES
('BANK_TRANSFER','Bank Transfer',10),
('MOBILE_MONEY','Mobile Money',20),
('CASH','Cash',30),
('CHEQUE','Cheque',40),
('OTHER','Other',99);

CREATE TABLE IF NOT EXISTS affiliate_payout_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payout_reference VARCHAR(60) NOT NULL UNIQUE,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  period_from DATE NULL,
  period_to DATE NULL,
  gross_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  payment_method_id BIGINT UNSIGNED NULL,
  status ENUM('Draft','Submitted','Approved','Paid','Rejected','Voided') NOT NULL DEFAULT 'Draft',
  submitted_at DATETIME NULL,
  approved_at DATETIME NULL,
  approved_by BIGINT UNSIGNED NULL,
  paid_at DATETIME NULL,
  paid_by BIGINT UNSIGNED NULL,
  payment_reference VARCHAR(120) NULL,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_affiliate_payout_batches_affiliate (affiliate_id),
  KEY idx_affiliate_payout_batches_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_payout_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payout_batch_id BIGINT UNSIGNED NOT NULL,
  commission_id BIGINT UNSIGNED NOT NULL,
  gross_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  tax_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  net_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_affiliate_payout_item_commission (commission_id),
  KEY idx_affiliate_payout_items_batch (payout_batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_agreements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  agreement_number VARCHAR(60) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  terms_html MEDIUMTEXT NOT NULL,
  status ENUM('Draft','Sent','Signed','Expired','Terminated') NOT NULL DEFAULT 'Draft',
  effective_date DATE NULL,
  expiry_date DATE NULL,
  sent_at DATETIME NULL,
  signed_at DATETIME NULL,
  signed_document_path VARCHAR(500) NULL,
  renewal_reminder_at DATE NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_affiliate_agreements_affiliate (affiliate_id),
  KEY idx_affiliate_agreements_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  visibility ENUM('All Affiliates','Specific Affiliate','Internal Note') NOT NULL DEFAULT 'Specific Affiliate',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_support_tickets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(60) NOT NULL UNIQUE,
  affiliate_id BIGINT UNSIGNED NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  priority ENUM('Low','Normal','High') NOT NULL DEFAULT 'Normal',
  admin_response TEXT NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS affiliate_login_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  affiliate_id BIGINT UNSIGNED NULL,
  email VARCHAR(190) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

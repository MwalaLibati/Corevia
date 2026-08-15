-- Corevia Scheduling Module Phase 1
-- Safe to run more than once.

CREATE TABLE IF NOT EXISTS shifts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  code VARCHAR(40) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  break_minutes INT NOT NULL DEFAULT 0,
  grace_minutes INT NOT NULL DEFAULT 0,
  expected_hours DECIMAL(6,2) NOT NULL DEFAULT 8.00,
  overtime_after_hours DECIMAL(6,2) NULL,
  color VARCHAR(20) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shifts_company_code (company_id, code),
  KEY idx_shifts_company_active (company_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_patterns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  code VARCHAR(40) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_patterns_company_code (company_id, code),
  KEY idx_patterns_company_active (company_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_pattern_days (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pattern_id BIGINT UNSIGNED NOT NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  shift_id BIGINT UNSIGNED NULL,
  is_working_day TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pattern_day (pattern_id, day_of_week),
  KEY idx_pattern_days_shift (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employee_schedule_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  employee_id BIGINT UNSIGNED NOT NULL,
  pattern_id BIGINT UNSIGNED NOT NULL,
  effective_from DATE NOT NULL,
  effective_to DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_assignments_company_employee (company_id, employee_id, effective_from, effective_to),
  KEY idx_assignments_pattern (pattern_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schedule_exceptions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  employee_id BIGINT UNSIGNED NOT NULL,
  exception_date DATE NOT NULL,
  shift_id BIGINT UNSIGNED NULL,
  exception_type ENUM('Shift Change','Rest Day','Public Holiday','Leave','Unscheduled Work') NOT NULL DEFAULT 'Shift Change',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_schedule_exception (company_id, employee_id, exception_date),
  KEY idx_schedule_exception_shift (shift_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO role_module_permissions (role_id, module_key)
SELECT r.id, 'scheduling'
FROM roles r
LEFT JOIN role_module_permissions existing ON existing.role_id = r.id AND existing.module_key = 'scheduling'
WHERE existing.role_id IS NULL
  AND (
    r.access_level IN ('Super Admin','Admin','HR Officer')
    OR EXISTS (
      SELECT 1 FROM role_module_permissions att
      WHERE att.role_id = r.id AND att.module_key = 'attendance'
    )
  );

INSERT INTO subscription_plan_modules (plan_id, module_key)
SELECT sp.id, 'scheduling'
FROM subscription_plans sp
LEFT JOIN subscription_plan_modules existing ON existing.plan_id = sp.id AND existing.module_key = 'scheduling'
WHERE existing.plan_id IS NULL
  AND EXISTS (
    SELECT 1 FROM subscription_plan_modules att
    WHERE att.plan_id = sp.id AND att.module_key = 'attendance'
  );

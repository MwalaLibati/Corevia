-- Corevia client entity and branch migration
-- Safe to run more than once where your MySQL version supports these checks through phpMyAdmin step-by-step.

CREATE TABLE IF NOT EXISTS client_entities (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    code VARCHAR(40) NULL,
    entity_type VARCHAR(80) NOT NULL DEFAULT 'Group',
    contact_person VARCHAR(190) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_entities_name (name),
    UNIQUE KEY uq_client_entities_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS branches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    code VARCHAR(40) NULL,
    phone VARCHAR(80) NULL,
    email VARCHAR(190) NULL,
    address TEXT NULL,
    city VARCHAR(120) NULL,
    manager_employee_id BIGINT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_branches_company_id (company_id),
    KEY idx_branches_manager_employee_id (manager_employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If these columns already exist, skip the matching ALTER TABLE statements.
ALTER TABLE companies ADD COLUMN client_entity_id BIGINT UNSIGNED NULL AFTER id;
ALTER TABLE companies ADD INDEX idx_companies_client_entity_id (client_entity_id);

ALTER TABLE employees ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER department_id;
ALTER TABLE employees ADD INDEX idx_employees_branch_id (branch_id);

-- Seed existing companies as their own default client entity, then attach them.
INSERT INTO client_entities (name, code, entity_type, email, phone, is_active)
SELECT c.name,
       CONCAT('ENT', LPAD(c.id, 4, '0')),
       'Single Company',
       c.email,
       c.phone,
       1
FROM companies c
LEFT JOIN client_entities ce ON ce.name = c.name
WHERE c.client_entity_id IS NULL
  AND ce.id IS NULL;

UPDATE companies c
JOIN client_entities ce ON ce.name = c.name
SET c.client_entity_id = ce.id
WHERE c.client_entity_id IS NULL;

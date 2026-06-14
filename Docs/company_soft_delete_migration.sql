-- Corevia company soft-delete support.
-- If any column already exists, skip that ALTER statement in phpMyAdmin.

ALTER TABLE companies ADD COLUMN deleted_at DATETIME NULL AFTER account_status;
ALTER TABLE companies ADD COLUMN deleted_by BIGINT UNSIGNED NULL AFTER deleted_at;
ALTER TABLE companies ADD COLUMN deletion_reason TEXT NULL AFTER deleted_by;

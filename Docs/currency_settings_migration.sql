-- Corevia company payroll currency setting
-- Run while logged into the target company database. Existing companies default to ZMW.

INSERT INTO settings (company_id, setting_key, setting_value)
SELECT c.id, 'payroll_currency', 'ZMW'
FROM companies c
WHERE NOT EXISTS (
    SELECT 1
    FROM settings s
    WHERE s.company_id = c.id
      AND s.setting_key = 'payroll_currency'
);

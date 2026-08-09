-- Corevia portal-only email notification migration
-- Purpose:
-- 1) Stop document attachments from being sent by email.
-- 2) Replace older wording such as "attached document" with secure portal instructions.
-- Safe to run more than once.

UPDATE settings
SET setting_value = '0'
WHERE setting_key IN (
    'email_template_contract_attach_document',
    'email_template_payslip_attach_document'
);

UPDATE settings
SET setting_value = 'Employment contract available in your employee portal'
WHERE setting_key = 'email_template_contract_subject';

UPDATE settings
SET setting_value = 'Dear {{employee_name}},

Your employment contract is now available in your employee self-service portal for review.

For confidentiality and security, contract documents are not sent by email. Please log in to your portal to view the contract details and any related actions.

Portal link: {{contract_url}}

Contract number: {{contract_number}}
Contract type: {{contract_type}}'
WHERE setting_key = 'email_template_contract_body';

UPDATE settings
SET setting_value = 'Payslip available in your employee portal - {{pay_period}}'
WHERE setting_key = 'email_template_payslip_subject';

UPDATE settings
SET setting_value = 'Dear {{employee_name}},

Your payslip for {{pay_period}} is now available in your employee self-service portal.

For confidentiality and security, payslip documents are not sent by email. Please log in to your portal to view or download your payslip.

Portal link: {{payslip_url}}

Net pay: {{net_pay}}.'
WHERE setting_key = 'email_template_payslip_body';

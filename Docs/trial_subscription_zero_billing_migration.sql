-- Corevia: Trial subscriptions must not carry billable value.
-- Safe to run more than once.

UPDATE subscriptions
SET monthly_rate = 0,
    price = 0
WHERE LOWER(plan) = 'trial';

UPDATE subscription_invoices si
JOIN subscriptions s ON s.id = si.subscription_id
SET si.subtotal = 0,
    si.tax_amount = 0,
    si.total_amount = 0,
    si.paid_amount = 0,
    si.balance_due = 0,
    si.status = CASE
        WHEN si.status IN ('Unpaid', 'Overdue') THEN 'Cancelled'
        ELSE si.status
    END,
    si.notes = COALESCE(NULLIF(si.notes, ''), 'Trial account - no billing value.')
WHERE LOWER(s.plan) = 'trial';

UPDATE subscription_invoice_lines sil
JOIN subscription_invoices si ON si.id = sil.invoice_id
JOIN subscriptions s ON s.id = si.subscription_id
SET sil.unit_price = 0,
    sil.line_total = 0
WHERE LOWER(s.plan) = 'trial';

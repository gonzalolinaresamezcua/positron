-- Migración desde versión de pago (Mollie) a POSITROM gratuito.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS payment_reminders;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS subscriptions;

DELETE FROM settings WHERE setting_key IN (
    'plan.price_eur',
    'plan.interval',
    'usage.monthly_budget_eur',
    'billing.auto_activate_on_payment',
    'billing.reminder_days_before',
    'cursor.model',
    'cursor.api_base',
    'cursor.chat_path'
);

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
    ('openai.model', 'gpt-6-astra', NOW()),
    ('openai.api_base', '', NOW()),
    ('openai.chat_path', '/v1/chat/completions', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

SET FOREIGN_KEY_CHECKS = 1;

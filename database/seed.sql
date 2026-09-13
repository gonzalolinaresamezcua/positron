-- Semilla de producción mínima. Credenciales del admin: solo en README.
SET NAMES utf8mb4;

INSERT INTO users (email, password_hash, name, role, is_active, created_at)
VALUES (
    'admin@positron.local',
    '$2y$10$7sBfanbpqiUSXquecWMEcuv5ByMXF0PE8GhT4XFzdiVbG4D.A.q6y',
    'Administración POSITRON',
    'admin',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
    ('plan.price_eur', '12.00', NOW()),
    ('plan.interval', '1 month', NOW()),
    ('usage.monthly_budget_eur', '12.00', NOW()),
    ('usage.token_input_cost_eur_per_1m', '0.50', NOW()),
    ('usage.token_output_cost_eur_per_1m', '2.50', NOW()),
    ('usage.monthly_token_allowance', '', NOW()),
    ('billing.auto_activate_on_payment', '1', NOW()),
    ('billing.reminder_days_before', '3', NOW()),
    ('cursor.model', 'composer-2.5', NOW()),
    ('cursor.api_base', '', NOW()),
    ('cursor.chat_path', '/v1/chat/completions', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

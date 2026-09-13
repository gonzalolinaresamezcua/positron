-- Semilla de producción mínima. Credenciales del admin: solo en README.
SET NAMES utf8mb4;

INSERT INTO users (email, password_hash, name, role, is_active, created_at)
VALUES (
    'admin@positrom.local',
    '$2y$10$caXmbTDA40N9eRvC1BKvUeiF4rnDT7WWjvEF1iwAMOaOeXHEUrnPa',
    'Administración POSITROM',
    'admin',
    1,
    NOW()
)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
    ('usage.token_input_cost_eur_per_1m', '0.50', NOW()),
    ('usage.token_output_cost_eur_per_1m', '2.50', NOW()),
    ('usage.monthly_token_allowance', '', NOW()),
    ('openai.model', 'gpt-6-astra', NOW()),
    ('openai.api_base', '', NOW()),
    ('openai.chat_path', '/v1/chat/completions', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

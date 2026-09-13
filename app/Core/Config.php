<?php

declare(strict_types=1);

namespace Positron\Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function boot(): void
    {
        self::$items = [
            'app.name' => Env::get('APP_NAME', 'POSITRON'),
            'app.env' => Env::get('APP_ENV', 'production'),
            'app.debug' => Env::get('APP_DEBUG', '0') === '1',
            'app.url' => rtrim((string) Env::get('APP_URL', ''), '/'),
            'app.key' => Env::get('APP_KEY', ''),
            'app.locale' => Env::get('APP_LOCALE', 'es'),
            'db.host' => Env::get('DB_HOST', 'localhost'),
            'db.port' => Env::get('DB_PORT', '3306'),
            'db.name' => Env::get('DB_NAME', 'positron'),
            'db.user' => Env::get('DB_USER', ''),
            'db.pass' => Env::get('DB_PASS', ''),
            'mollie.key' => Env::get('MOLLIE_API_KEY', ''),
            'mollie.base' => rtrim((string) Env::get('MOLLIE_API_BASE', 'https://api.mollie.com/v2'), '/'),
            'cursor.key' => Env::get('CURSOR_API_KEY', ''),
            'cursor.base' => rtrim((string) Env::get('CURSOR_API_BASE', 'https://api.cursor.com'), '/'),
            'cursor.model' => Env::get('CURSOR_MODEL', 'composer-2.5'),
            'cursor.path' => Env::get('CURSOR_CHAT_PATH', '/v1/chat/completions'),
            'plan.price' => (float) Env::get('PLAN_PRICE_EUR', '12.00'),
            'plan.interval' => Env::get('PLAN_INTERVAL', '1 month'),
            'usage.budget' => (float) Env::get('MONTHLY_BUDGET_EUR', '12.00'),
            'usage.input_cost' => (float) Env::get('TOKEN_INPUT_COST_EUR_PER_1M', '0.50'),
            'usage.output_cost' => (float) Env::get('TOKEN_OUTPUT_COST_EUR_PER_1M', '2.50'),
            'usage.token_allowance' => Env::get('MONTHLY_TOKEN_ALLOWANCE', ''),
            'billing.auto_activate' => Env::get('AUTO_ACTIVATE_ON_PAYMENT', '1') === '1',
            'billing.reminder_days' => (int) Env::get('REMINDER_DAYS_BEFORE', '3'),
            'smtp.host' => Env::get('SMTP_HOST', ''),
            'smtp.port' => (int) Env::get('SMTP_PORT', '587'),
            'smtp.user' => Env::get('SMTP_USER', ''),
            'smtp.pass' => Env::get('SMTP_PASS', ''),
            'smtp.from' => Env::get('SMTP_FROM', ''),
            'smtp.from_name' => Env::get('SMTP_FROM_NAME', 'POSITRON'),
            'smtp.encryption' => Env::get('SMTP_ENCRYPTION', 'tls'),
            'session.name' => Env::get('SESSION_NAME', 'positron_sess'),
            'chat.rate' => (int) Env::get('CHAT_RATE_PER_MINUTE', '30'),
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$items[$key] = $value;
    }

    public static function isPlaceholderSecret(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }
        return str_contains($value, 'xxxx') || str_contains($value, 'change_this');
    }
}

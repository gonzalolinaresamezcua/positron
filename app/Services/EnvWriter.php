<?php

declare(strict_types=1);

namespace Positron\Services;

use Positron\Core\Config;
use Positron\Core\Env;

final class EnvWriter
{
    /** @var list<string> */
    public const ALLOWED = [
        'APP_URL',
        'APP_KEY',
        'APP_ENV',
        'APP_DEBUG',
        'DB_HOST',
        'DB_PORT',
        'DB_SOCKET',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'MOLLIE_API_KEY',
        'MOLLIE_API_BASE',
        'CURSOR_API_KEY',
        'CURSOR_API_BASE',
        'CURSOR_MODEL',
        'CURSOR_CHAT_PATH',
        'SMTP_HOST',
        'SMTP_PORT',
        'SMTP_USER',
        'SMTP_PASS',
        'SMTP_FROM',
        'SMTP_FROM_NAME',
        'SMTP_ENCRYPTION',
        'PLAN_PRICE_EUR',
        'MONTHLY_BUDGET_EUR',
        'TOKEN_INPUT_COST_EUR_PER_1M',
        'TOKEN_OUTPUT_COST_EUR_PER_1M',
        'MONTHLY_TOKEN_ALLOWANCE',
        'AUTO_ACTIVATE_ON_PAYMENT',
        'REMINDER_DAYS_BEFORE',
    ];

    public static function path(): string
    {
        return POSITRON_ROOT . '/.env';
    }

    /**
     * @param array<string, string> $updates
     * @return list<string> keys written
     */
    public static function update(array $updates): array
    {
        $allowed = array_fill_keys(self::ALLOWED, true);
        $clean = [];
        foreach ($updates as $key => $value) {
            if (!isset($allowed[$key])) {
                continue;
            }
            if (!is_string($value)) {
                $value = (string) $value;
            }
            if (str_contains($value, "\n") || str_contains($value, "\r")) {
                throw new \InvalidArgumentException('Valor de entorno no válido.');
            }
            $clean[$key] = $value;
        }
        if ($clean === []) {
            return [];
        }

        $path = self::path();
        if (!is_file($path)) {
            throw new \RuntimeException('No existe el archivo .env');
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('No se pudo leer .env');
        }

        $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
        $seen = [];
        $out = [];
        foreach ($lines as $line) {
            if ($line === '' || str_starts_with(ltrim($line), '#')) {
                $out[] = $line;
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                $out[] = $line;
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            if (isset($clean[$key])) {
                $out[] = $key . '=' . self::encode($clean[$key]);
                $seen[$key] = true;
            } else {
                $out[] = $line;
            }
        }
        foreach ($clean as $key => $value) {
            if (!isset($seen[$key])) {
                $out[] = $key . '=' . self::encode($value);
            }
        }

        $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
        $body = implode(PHP_EOL, $out);
        if (!str_ends_with($body, PHP_EOL)) {
            $body .= PHP_EOL;
        }
        if (file_put_contents($tmp, $body, LOCK_EX) === false) {
            throw new \RuntimeException('No se pudo escribir .env temporal');
        }
        @chmod($tmp, 0600);
        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('No se pudo reemplazar .env');
        }
        @chmod($path, 0600);

        Env::reload($path);
        Config::boot();
        return array_keys($clean);
    }

    private static function encode(string $value): string
    {
        if ($value === '' || !preg_match('/[\s#"\']/', $value)) {
            return $value;
        }
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }
}

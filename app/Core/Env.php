<?php

declare(strict_types=1);

namespace Positron\Core;

final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('No se puede leer el archivo .env');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new \RuntimeException('No se puede leer el archivo .env');
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            if ($key === '') {
                continue;
            }
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            self::$values[$key] = $value;
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }
        $fromEnv = getenv($key);
        if ($fromEnv !== false) {
            return $fromEnv;
        }
        return $default;
    }

    public static function reload(string $path): void
    {
        self::$values = [];
        self::load($path);
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::$values;
    }
}

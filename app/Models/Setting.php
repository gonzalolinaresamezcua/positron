<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class Setting
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        } catch (\Throwable) {
            self::$cache = [];
            return self::$cache;
        }
        $out = [];
        foreach ($rows as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        self::$cache = $out;
        return $out;
    }

    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
            [$key, $value, now()]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    /** @param array<string, string> $pairs */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}

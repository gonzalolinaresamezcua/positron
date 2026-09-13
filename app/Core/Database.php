<?php

declare(strict_types=1);

namespace Positron\Core;

use PDO;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;

    public static function boot(): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }
        $socket = Env::get('DB_SOCKET', '');
        if ($socket !== null && $socket !== '') {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=utf8mb4',
                $socket,
                Config::get('db.name')
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('db.host'),
                Config::get('db.port'),
                Config::get('db.name')
            );
        }
        self::$pdo = new PDO($dsn, (string) Config::get('db.user'), (string) Config::get('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            self::boot();
        }
        return self::$pdo;
    }

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /** @param array<int|string, mixed> $params */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @param array<int|string, mixed> $params */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @param array<int|string, mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        $val = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    public static function lastId(): string
    {
        return self::pdo()->lastInsertId();
    }
}

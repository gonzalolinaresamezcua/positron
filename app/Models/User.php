<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class User
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [mb_strtolower($email)]);
    }

    public static function create(string $email, string $password, string $name, string $role = 'user'): int
    {
        Database::query(
            'INSERT INTO users (email, password_hash, name, role, is_active, created_at)
             VALUES (?, ?, ?, ?, 1, ?)',
            [mb_strtolower($email), password_hash($password, PASSWORD_DEFAULT), $name, $role, now()]
        );
        return (int) Database::lastId();
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public static function touchLogin(int $id): void
    {
        Database::query('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::query('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    /** @return list<array<string, mixed>> */
    public static function paginate(int $page, int $perPage, ?string $q = null): array
    {
        $perPage = max(1, min(100, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        if ($q !== null && $q !== '') {
            $like = '%' . $q . '%';
            return Database::fetchAll(
                "SELECT * FROM users WHERE email LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
                [$like, $like]
            );
        }
        return Database::fetchAll(
            "SELECT * FROM users ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}"
        );
    }

    public static function count(?string $q = null): int
    {
        if ($q !== null && $q !== '') {
            $like = '%' . $q . '%';
            return (int) Database::value('SELECT COUNT(*) FROM users WHERE email LIKE ? OR name LIKE ?', [$like, $like]);
        }
        return (int) Database::value('SELECT COUNT(*) FROM users');
    }

    public static function countByRole(string $role): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM users WHERE role = ?', [$role]);
    }
}

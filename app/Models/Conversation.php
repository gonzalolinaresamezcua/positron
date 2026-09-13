<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class Conversation
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM conversations WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM conversations WHERE user_id = ? ORDER BY updated_at DESC',
            [$userId]
        );
    }

    public static function create(int $userId, string $title = 'Nueva conversación'): int
    {
        $ts = now();
        Database::query(
            'INSERT INTO conversations (user_id, title, created_at, updated_at) VALUES (?, ?, ?, ?)',
            [$userId, $title, $ts, $ts]
        );
        return (int) Database::lastId();
    }

    public static function touch(int $id, ?string $title = null): void
    {
        if ($title !== null) {
            Database::query(
                'UPDATE conversations SET title = ?, updated_at = ? WHERE id = ?',
                [$title, now(), $id]
            );
            return;
        }
        Database::query('UPDATE conversations SET updated_at = ? WHERE id = ?', [now(), $id]);
    }

    public static function ownedBy(int $id, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM conversations WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );
    }
}

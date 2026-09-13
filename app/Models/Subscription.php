<?php

declare(strict_types=1);

namespace Positron\Models;

use Positron\Core\Database;

final class Subscription
{
    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM subscriptions WHERE id = ? LIMIT 1', [$id]);
    }

    public static function forUser(int $userId): ?array
    {
        return Database::fetch('SELECT * FROM subscriptions WHERE user_id = ? LIMIT 1', [$userId]);
    }

    public static function createForUser(int $userId, float $price): int
    {
        $existing = self::forUser($userId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }
        $ts = now();
        Database::query(
            'INSERT INTO subscriptions (user_id, status, plan_price_eur, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)',
            [$userId, 'incomplete', $price, $ts, $ts]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $fields */
    public static function update(int $id, array $fields): void
    {
        if ($fields === []) {
            return;
        }
        $fields['updated_at'] = now();
        $sets = [];
        $params = [];
        foreach ($fields as $col => $value) {
            if (!preg_match('/^[a-z_]+$/', $col)) {
                throw new \InvalidArgumentException('Columna no permitida');
            }
            $sets[] = $col . ' = ?';
            $params[] = $value;
        }
        $params[] = $id;
        Database::query('UPDATE subscriptions SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    public static function isChatAllowed(?array $sub): bool
    {
        return $sub !== null && $sub['status'] === 'active';
    }

    /** @return list<array<string, mixed>> */
    public static function dueForReminder(int $days): array
    {
        return Database::fetchAll(
            'SELECT s.*, u.email, u.name
             FROM subscriptions s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.status IN (\'active\', \'past_due\')
               AND s.next_payment_date IS NOT NULL
               AND s.next_payment_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)',
            [$days]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function allWithUsers(): array
    {
        return Database::fetchAll(
            'SELECT s.*, u.email, u.name
             FROM subscriptions s
             INNER JOIN users u ON u.id = s.user_id
             ORDER BY s.updated_at DESC'
        );
    }
}

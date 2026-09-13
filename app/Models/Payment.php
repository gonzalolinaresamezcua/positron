<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class Payment
{
    public static function findByMollieId(string $id): ?array
    {
        return Database::fetch('SELECT * FROM payments WHERE mollie_payment_id = ? LIMIT 1', [$id]);
    }

    /** @param array<string, mixed> $data */
    public static function upsertFromMollie(array $data): int
    {
        $existing = self::findByMollieId($data['mollie_payment_id']);
        $ts = now();
        if ($existing !== null) {
            Database::query(
                'UPDATE payments
                 SET status = ?, method = ?, sequence_type = ?, paid_at = ?, raw_json = ?, updated_at = ?
                 WHERE id = ?',
                [
                    $data['status'],
                    $data['method'] ?? null,
                    $data['sequence_type'] ?? null,
                    $data['paid_at'] ?? null,
                    $data['raw_json'] ?? null,
                    $ts,
                    $existing['id'],
                ]
            );
            return (int) $existing['id'];
        }
        Database::query(
            'INSERT INTO payments (
                user_id, subscription_id, mollie_payment_id, amount_eur, currency, status,
                method, sequence_type, is_first, paid_at, raw_json, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['subscription_id'] ?? null,
                $data['mollie_payment_id'],
                $data['amount_eur'],
                $data['currency'] ?? 'EUR',
                $data['status'],
                $data['method'] ?? null,
                $data['sequence_type'] ?? null,
                !empty($data['is_first']) ? 1 : 0,
                $data['paid_at'] ?? null,
                $data['raw_json'] ?? null,
                $ts,
                $ts,
            ]
        );
        return (int) Database::lastId();
    }

    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC',
            [$userId]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        return Database::fetchAll(
            "SELECT p.*, u.email, u.name
             FROM payments p
             INNER JOIN users u ON u.id = p.user_id
             ORDER BY p.id DESC
             LIMIT {$limit}"
        );
    }

    public static function sumPaid(): float
    {
        return (float) Database::value(
            "SELECT COALESCE(SUM(amount_eur), 0) FROM payments WHERE status = 'paid'"
        );
    }

    public static function countPaid(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM payments WHERE status = 'paid'");
    }
}

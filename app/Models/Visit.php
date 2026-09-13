<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class Visit
{
    public static function record(string $path, string $ipHash, ?string $userAgent, ?int $userId): void
    {
        Database::query(
            'INSERT INTO page_visits (path, ip_hash, user_agent, user_id, created_at)
             VALUES (?, ?, ?, ?, ?)',
            [$path, $ipHash, $userAgent !== null ? mb_substr($userAgent, 0, 255) : null, $userId, now()]
        );
    }

    public static function countAll(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM page_visits');
    }

    public static function countSince(string $since): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM page_visits WHERE created_at >= ?', [$since]);
    }

    /** @return list<array<string, mixed>> */
    public static function daily(int $days = 14): array
    {
        return Database::fetchAll(
            'SELECT DATE(created_at) AS day, COUNT(*) AS visits
             FROM page_visits
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [$days]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function topPaths(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return Database::fetchAll(
            "SELECT path, COUNT(*) AS visits
             FROM page_visits
             GROUP BY path
             ORDER BY visits DESC
             LIMIT {$limit}"
        );
    }
}

<?php

declare(strict_types=1);

namespace Positrom\Models;

use Positrom\Core\Database;

final class UsageEvent
{
    public static function record(
        int $userId,
        ?int $conversationId,
        string $model,
        int $tokensIn,
        int $tokensOut,
        float $costEur,
        ?string $periodYm = null
    ): int {
        Database::query(
            'INSERT INTO usage_events (user_id, conversation_id, model, tokens_in, tokens_out, cost_eur, period_ym, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $conversationId,
                $model,
                $tokensIn,
                $tokensOut,
                number_format($costEur, 6, '.', ''),
                $periodYm ?? period_ym(),
                now(),
            ]
        );
        return (int) Database::lastId();
    }

    public static function monthTotals(int $userId, ?string $periodYm = null): array
    {
        $periodYm ??= period_ym();
        $row = Database::fetch(
            'SELECT COALESCE(SUM(tokens_in), 0) AS tokens_in,
                    COALESCE(SUM(tokens_out), 0) AS tokens_out,
                    COALESCE(SUM(cost_eur), 0) AS cost_eur,
                    COUNT(*) AS requests
             FROM usage_events
             WHERE user_id = ? AND period_ym = ?',
            [$userId, $periodYm]
        );
        return $row ?? ['tokens_in' => 0, 'tokens_out' => 0, 'cost_eur' => 0, 'requests' => 0];
    }

    public static function globalTotals(?string $periodYm = null): array
    {
        if ($periodYm !== null) {
            $row = Database::fetch(
                'SELECT COALESCE(SUM(tokens_in), 0) AS tokens_in,
                        COALESCE(SUM(tokens_out), 0) AS tokens_out,
                        COALESCE(SUM(cost_eur), 0) AS cost_eur,
                        COUNT(*) AS requests
                 FROM usage_events WHERE period_ym = ?',
                [$periodYm]
            );
        } else {
            $row = Database::fetch(
                'SELECT COALESCE(SUM(tokens_in), 0) AS tokens_in,
                        COALESCE(SUM(tokens_out), 0) AS tokens_out,
                        COALESCE(SUM(cost_eur), 0) AS cost_eur,
                        COUNT(*) AS requests
                 FROM usage_events'
            );
        }
        return $row ?? ['tokens_in' => 0, 'tokens_out' => 0, 'cost_eur' => 0, 'requests' => 0];
    }

    /** @return list<array<string, mixed>> */
    public static function daily(int $days = 14): array
    {
        return Database::fetchAll(
            'SELECT DATE(created_at) AS day,
                    COUNT(*) AS requests,
                    COALESCE(SUM(tokens_in + tokens_out), 0) AS tokens,
                    COALESCE(SUM(cost_eur), 0) AS cost_eur
             FROM usage_events
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
            [$days]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function topUsers(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        return Database::fetchAll(
            "SELECT u.id, u.email, u.name,
                    COALESCE(SUM(e.tokens_in + e.tokens_out), 0) AS tokens,
                    COALESCE(SUM(e.cost_eur), 0) AS cost_eur,
                    COUNT(e.id) AS requests
             FROM users u
             LEFT JOIN usage_events e ON e.user_id = u.id AND e.period_ym = ?
             WHERE u.role = 'user'
             GROUP BY u.id, u.email, u.name
             ORDER BY cost_eur DESC
             LIMIT {$limit}",
            [period_ym()]
        );
    }
}

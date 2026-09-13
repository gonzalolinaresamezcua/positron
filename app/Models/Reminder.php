<?php

declare(strict_types=1);

namespace Positron\Models;

use Positron\Core\Database;

final class Reminder
{
    public static function alreadySent(int $userId, string $periodYm): bool
    {
        $row = Database::fetch(
            'SELECT id FROM payment_reminders WHERE user_id = ? AND period_ym = ? LIMIT 1',
            [$userId, $periodYm]
        );
        return $row !== null;
    }

    public static function mark(int $userId, string $periodYm): void
    {
        Database::query(
            'INSERT IGNORE INTO payment_reminders (user_id, period_ym, sent_at) VALUES (?, ?, ?)',
            [$userId, $periodYm, now()]
        );
    }
}

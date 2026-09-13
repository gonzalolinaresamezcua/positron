<?php

declare(strict_types=1);

namespace Positron\Models;

use Positron\Core\Database;

final class EmailLog
{
    public static function write(
        ?int $userId,
        string $type,
        string $recipient,
        string $subject,
        string $status,
        ?string $error = null
    ): void {
        Database::query(
            'INSERT INTO email_logs (user_id, type, recipient, subject, status, error_text, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$userId, $type, $recipient, $subject, $status, $error, now()]
        );
    }
}

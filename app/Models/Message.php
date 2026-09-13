<?php

declare(strict_types=1);

namespace Positron\Models;

use Positron\Core\Database;

final class Message
{
    public static function add(int $conversationId, string $role, string $content, int $tokensIn = 0, int $tokensOut = 0): int
    {
        Database::query(
            'INSERT INTO messages (conversation_id, role, content, tokens_in, tokens_out, created_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$conversationId, $role, $content, $tokensIn, $tokensOut, now()]
        );
        return (int) Database::lastId();
    }

    /** @return list<array<string, mixed>> */
    public static function forConversation(int $conversationId): array
    {
        return Database::fetchAll(
            'SELECT * FROM messages WHERE conversation_id = ? ORDER BY id ASC',
            [$conversationId]
        );
    }
}

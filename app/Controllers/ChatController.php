<?php

declare(strict_types=1);

namespace Positron\Controllers;

use Positron\Core\Auth;
use Positron\Core\Config;
use Positron\Core\View;
use Positron\Models\Conversation;
use Positron\Models\Message;
use Positron\Models\Subscription;
use Positron\Models\UsageEvent;
use Positron\Services\CursorClient;
use Positron\Services\CursorNotConfiguredException;
use Positron\Services\CursorRequestException;
use Positron\Services\UsageExhaustedException;
use Positron\Services\UsageLimiter;

final class ChatController
{
    public function index(): void
    {
        $user = Auth::requireUser();
        $sub = Subscription::forUser((int) $user['id']);
        if (!Subscription::isChatAllowed($sub)) {
            set_flash('error', 'Necesitas una suscripción activa para usar el chat.');
            redirect('/checkout');
        }
        $conversations = Conversation::forUser((int) $user['id']);
        $currentId = isset($_GET['c']) ? (int) $_GET['c'] : (int) ($conversations[0]['id'] ?? 0);
        $current = $currentId > 0 ? Conversation::ownedBy($currentId, (int) $user['id']) : null;
        $messages = $current !== null ? Message::forConversation((int) $current['id']) : [];
        $usage = (new UsageLimiter())->snapshot((int) $user['id']);
        View::render('chat/index', [
            'title' => 'Chat POSITRON',
            'user' => $user,
            'subscription' => $sub,
            'conversations' => $conversations,
            'current' => $current,
            'messages' => $messages,
            'usage' => $usage,
        ], 'layouts/app');
    }

    public function createConversation(): void
    {
        $user = Auth::requireUser();
        $this->guardSub($user);
        $id = Conversation::create((int) $user['id']);
        redirect('/chat?c=' . $id);
    }

    public function send(): void
    {
        $user = Auth::requireUser();
        $this->guardSub($user);
        $userId = (int) $user['id'];

        $limiter = new UsageLimiter();
        $max = (int) Config::get('chat.rate', 30);
        if ($limiter->hitRateLimit($userId, $max)) {
            json_response(['ok' => false, 'error' => 'Demasiadas peticiones. Espera un momento.'], 429);
        }

        try {
            $limiter->assertCanSpend($userId);
        } catch (UsageExhaustedException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage(), 'exhausted' => true], 402);
        }

        $text = trim((string) ($_POST['message'] ?? ''));
        if ($text === '' || mb_strlen($text) > 8000) {
            json_response(['ok' => false, 'error' => 'Escribe un mensaje (máximo 8000 caracteres).'], 422);
        }

        $convId = (int) ($_POST['conversation_id'] ?? 0);
        $conv = $convId > 0 ? Conversation::ownedBy($convId, $userId) : null;
        if ($conv === null) {
            $convId = Conversation::create($userId, $this->titleFrom($text));
            $conv = Conversation::find($convId);
        }

        Message::add((int) $conv['id'], 'user', $text);
        if (($conv['title'] ?? '') === 'Nueva conversación') {
            Conversation::touch((int) $conv['id'], $this->titleFrom($text));
        } else {
            Conversation::touch((int) $conv['id']);
        }

        $history = Message::forConversation((int) $conv['id']);
        $payload = [['role' => 'system', 'content' => CursorClient::systemPrompt()]];
        foreach ($history as $row) {
            if ($row['role'] === 'system') {
                continue;
            }
            $payload[] = ['role' => $row['role'], 'content' => $row['content']];
        }
        $system = array_shift($payload);
        $payload = array_slice($payload, -24);
        if (is_array($system)) {
            array_unshift($payload, $system);
        }

        $client = new CursorClient();
        try {
            $completion = $client->complete($payload);
        } catch (CursorNotConfiguredException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 503);
        } catch (CursorRequestException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 502);
        }

        $cost = $limiter->estimateCost($completion->tokensIn, $completion->tokensOut);
        UsageEvent::record(
            $userId,
            (int) $conv['id'],
            $completion->model,
            $completion->tokensIn,
            $completion->tokensOut,
            $cost
        );
        Message::add((int) $conv['id'], 'assistant', $completion->text, $completion->tokensIn, $completion->tokensOut);

        $after = $limiter->snapshot($userId);
        json_response([
            'ok' => true,
            'conversation_id' => (int) $conv['id'],
            'reply' => $completion->text,
            'model' => $completion->model,
            'tokens_in' => $completion->tokensIn,
            'tokens_out' => $completion->tokensOut,
            'cost_eur' => $cost,
            'usage' => $after,
        ]);
    }

    private function guardSub(array $user): void
    {
        $sub = Subscription::forUser((int) $user['id']);
        if (!Subscription::isChatAllowed($sub)) {
            if (\Positron\Core\Router::wantsJson() || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
                json_response(['ok' => false, 'error' => 'Suscripción inactiva.'], 403);
            }
            redirect('/checkout');
        }
    }

    private function titleFrom(string $text): string
    {
        $t = preg_replace('/\s+/', ' ', $text) ?? $text;
        return mb_substr($t, 0, 72);
    }
}

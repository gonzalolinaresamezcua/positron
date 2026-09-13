<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Core\Config;
use Positrom\Http\CurlTransport;
use Positrom\Http\HttpTransport;
use Positrom\Models\Setting;

final class CursorClient
{
    public function __construct(private ?HttpTransport $transport = null)
    {
        $this->transport ??= new CurlTransport(90);
    }

    public function apiKey(): string
    {
        return trim((string) Config::get('cursor.key', ''));
    }

    public function baseUrl(): string
    {
        $fromSettings = trim((string) Setting::get('cursor.api_base', ''));
        $base = $fromSettings !== '' ? $fromSettings : (string) Config::get('cursor.base', 'https://api.cursor.com');
        return rtrim($base, '/');
    }

    public function model(): string
    {
        $fromSettings = trim((string) Setting::get('cursor.model', ''));
        return $fromSettings !== '' ? $fromSettings : (string) Config::get('cursor.model', 'composer-2.5');
    }

    public function chatPath(): string
    {
        $fromSettings = trim((string) Setting::get('cursor.chat_path', ''));
        $path = $fromSettings !== '' ? $fromSettings : (string) Config::get('cursor.path', '/v1/chat/completions');
        return '/' . ltrim($path, '/');
    }

    public function configured(): bool
    {
        $key = $this->apiKey();
        return $key !== '' && !Config::isPlaceholderSecret($key);
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     */
    public function complete(array $messages): CursorCompletion
    {
        if (!$this->configured()) {
            throw new CursorNotConfiguredException(
                'El cliente Cursor no está configurado. Un administrador debe guardar CURSOR_API_KEY (y la URL base) en Ajustes.'
            );
        }

        $url = $this->baseUrl() . $this->chatPath();
        $payload = [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => 0.7,
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('No se pudo serializar la petición a Cursor.');
        }

        $response = $this->transport->request('POST', $url, [
            'Authorization' => 'Bearer ' . $this->apiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $body);

        $json = $response->json();
        if (!$response->ok() || $json === null) {
            $hint = $json['error']['message'] ?? $json['message'] ?? mb_substr($response->body, 0, 240);
            throw new CursorRequestException(
                'Cursor no respondió correctamente (' . $response->status . '): ' . $hint,
                $response->status
            );
        }

        $text = $json['choices'][0]['message']['content']
            ?? $json['output_text']
            ?? $json['message']['content']
            ?? null;
        if (!is_string($text) || $text === '') {
            throw new CursorRequestException('La respuesta de Cursor no incluye texto utilizable.', $response->status);
        }

        $in = (int) ($json['usage']['prompt_tokens'] ?? $json['usage']['input_tokens'] ?? 0);
        $out = (int) ($json['usage']['completion_tokens'] ?? $json['usage']['output_tokens'] ?? 0);
        if ($in === 0 && $out === 0) {
            $in = self::estimateTokens(implode("\n", array_map(static fn ($m) => $m['content'], $messages)));
            $out = self::estimateTokens($text);
        }

        return new CursorCompletion($text, $this->model(), $in, $out, $json);
    }

    public static function estimateTokens(string $text): int
    {
        $chars = max(1, mb_strlen($text));
        return (int) max(1, ceil($chars / 4));
    }

    public static function systemPrompt(): string
    {
        return 'Eres POSITROM, un asistente de IA de la suscripción POSITROM. '
            . 'Respondes en español salvo que el usuario pida otro idioma. '
            . 'Eres preciso, útil y directo. El modelo subyacente es composer-2.5 de Cursor.';
    }
}

final class CursorCompletion
{
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly int $tokensIn,
        public readonly int $tokensOut,
        public readonly array $raw
    ) {
    }
}

class CursorNotConfiguredException extends \RuntimeException
{
}

class CursorRequestException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 0)
    {
        parent::__construct($message);
    }
}

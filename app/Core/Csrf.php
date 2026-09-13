<?php

declare(strict_types=1);

namespace Positrom\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function verify(?string $token): bool
    {
        $stored = Session::get('_csrf');
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }

    public static function fromRequest(): ?string
    {
        if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
            return $_POST['_csrf'];
        }
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return is_string($header) ? $header : null;
    }
}

<?php

declare(strict_types=1);

use Positrom\Core\Auth;
use Positrom\Core\Config;
use Positrom\Core\Csrf;
use Positrom\Core\Session;
use Positrom\Models\Setting;

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function setting(string $key, mixed $default = null): mixed
{
    return Setting::get($key, $default);
}

function url(string $path = '/'): string
{
    $base = rtrim((string) Config::get('app.url', ''), '/');
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return $base !== '' ? $base . '/' : '/';
    }
    return ($base !== '' ? $base : '') . $path;
}

function redirect(string $path, int $code = 302): never
{
    if (!headers_sent()) {
        header('Location: ' . $path, true, $code);
    }
    exit;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function old(string $key, string $default = ''): string
{
    $old = Session::get('_old', []);
    return isset($old[$key]) ? (string) $old[$key] : $default;
}

function flash(string $key): ?string
{
    return Session::pull('flash_' . $key);
}

function set_flash(string $type, string $message): void
{
    Session::set('flash_' . $type, $message);
}

function money_eur(float|string $amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

function current_user(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::isAdmin();
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function period_ym(?string $date = null): string
{
    return $date === null ? date('Y-m') : date('Y-m', strtotime($date));
}

function mask_secret(?string $value, int $keep = 4): string
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    $keep = min($keep, strlen($value));
    return str_repeat('•', max(0, strlen($value) - $keep)) . substr($value, -$keep);
}

function request_path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return $path !== null && $path !== '' ? $path : '/';
}

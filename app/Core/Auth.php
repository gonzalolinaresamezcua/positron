<?php

declare(strict_types=1);

namespace Positrom\Core;

use Positrom\Models\User;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || !(int) $user['is_active']) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        User::touchLogin((int) $user['id']);
        return true;
    }

    public static function loginId(int $userId): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::regenerate();
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        return User::find($id);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    public static function requireUser(): array
    {
        $user = self::user();
        if ($user === null) {
            set_flash('error', 'Inicia sesión para continuar.');
            redirect('/acceso');
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireUser();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado'], 'layouts/public');
            exit;
        }
        return $user;
    }
}

<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Core\Auth;
use Positrom\Core\Session;
use Positrom\Core\Validator;
use Positrom\Core\View;
use Positrom\Models\Subscription;
use Positrom\Models\User;
use Positrom\Services\BillingService;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::isAdmin() ? '/admin' : '/chat');
        }
        View::render('auth/login', ['title' => 'Acceso'], 'layouts/auth');
    }

    public function login(): void
    {
        $v = new Validator($_POST);
        $v->required('email', 'El correo')->email('email', 'El correo')->required('password', 'La contraseña');
        if (!$v->ok()) {
            Session::set('_old', ['email' => (string) ($_POST['email'] ?? '')]);
            set_flash('error', $v->first());
            redirect('/acceso');
        }
        if (!Auth::attempt((string) $_POST['email'], (string) $_POST['password'])) {
            Session::set('_old', ['email' => (string) $_POST['email']]);
            set_flash('error', 'Credenciales no válidas.');
            redirect('/acceso');
        }
        Session::forget('_old');
        redirect(Auth::isAdmin() ? '/admin' : '/chat');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/chat');
        }
        View::render('auth/register', ['title' => 'Crear cuenta'], 'layouts/auth');
    }

    public function register(): void
    {
        $v = new Validator($_POST);
        $v->required('name', 'El nombre')->max('name', 120, 'El nombre')
            ->required('email', 'El correo')->email('email', 'El correo')
            ->required('password', 'La contraseña')->min('password', 10, 'La contraseña')
            ->required('password_confirmation', 'La confirmación')
            ->same('password_confirmation', 'password', 'La confirmación');
        Session::set('_old', [
            'name' => (string) ($_POST['name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
        ]);
        if (!$v->ok()) {
            set_flash('error', $v->first());
            redirect('/registro');
        }
        if (User::findByEmail((string) $_POST['email']) !== null) {
            set_flash('error', 'Ya existe una cuenta con ese correo.');
            redirect('/registro');
        }
        $id = User::create((string) $_POST['email'], (string) $_POST['password'], trim((string) $_POST['name']));
        $billing = new BillingService();
        Subscription::createForUser($id, $billing->planPrice());
        Auth::loginId($id);
        Session::forget('_old');
        set_flash('ok', 'Cuenta creada. Activa tu órbita mensual para entrar al chat.');
        redirect('/checkout');
    }

    public function logout(): void
    {
        Auth::logout();
        set_flash('ok', 'Sesión cerrada.');
        redirect('/');
    }
}

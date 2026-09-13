<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Core\Auth;
use Positrom\Core\View;
use Positrom\Models\Subscription;
use Positrom\Services\BillingService;
use Positrom\Services\MollieNotConfiguredException;

final class CheckoutController
{
    public function index(): void
    {
        $user = Auth::requireUser();
        $sub = Subscription::forUser((int) $user['id']);
        View::render('checkout/index', [
            'title' => 'Activar POSITROM',
            'user' => $user,
            'subscription' => $sub,
            'price' => (new BillingService())->planPrice(),
        ], 'layouts/app');
    }

    public function start(): void
    {
        $user = Auth::requireUser();
        $sub = Subscription::forUser((int) $user['id']);
        if ($sub !== null && $sub['status'] === 'active') {
            set_flash('ok', 'Tu suscripción ya está activa.');
            redirect('/chat');
        }
        try {
            $url = (new BillingService())->startCheckout($user);
        } catch (MollieNotConfiguredException $e) {
            set_flash('error', $e->getMessage());
            redirect('/checkout');
        } catch (\Throwable $e) {
            set_flash('error', 'No se pudo iniciar el pago: ' . $e->getMessage());
            redirect('/checkout');
        }
        redirect($url);
    }

    public function retorno(): void
    {
        $user = Auth::requireUser();
        $sub = Subscription::forUser((int) $user['id']);
        View::render('checkout/retorno', [
            'title' => 'Pago en proceso',
            'user' => $user,
            'subscription' => $sub,
        ], 'layouts/app');
    }
}

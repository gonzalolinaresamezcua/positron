<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Core\Auth;
use Positrom\Core\View;
use Positrom\Models\Payment;
use Positrom\Models\Subscription;
use Positrom\Services\BillingService;
use Positrom\Services\UsageLimiter;

final class AccountController
{
    public function index(): void
    {
        $user = Auth::requireUser();
        $sub = Subscription::forUser((int) $user['id']);
        $payments = Payment::forUser((int) $user['id']);
        $usage = (new UsageLimiter())->snapshot((int) $user['id']);
        View::render('account/index', [
            'title' => 'Tu cuenta',
            'user' => $user,
            'subscription' => $sub,
            'payments' => $payments,
            'usage' => $usage,
        ], 'layouts/app');
    }

    public function cancel(): void
    {
        $user = Auth::requireUser();
        (new BillingService())->cancel((int) $user['id'], 'Cancelada por el cliente');
        set_flash('ok', 'Suscripción cancelada. El chat quedará cerrado al finalizar el acceso activo.');
        redirect('/cuenta');
    }
}

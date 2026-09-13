<?php

declare(strict_types=1);

namespace Positron\Controllers;

use Positron\Core\Auth;
use Positron\Core\View;
use Positron\Models\Payment;
use Positron\Models\Subscription;
use Positron\Services\BillingService;
use Positron\Services\UsageLimiter;

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

<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Core\Auth;
use Positrom\Core\View;
use Positrom\Services\UsageLimiter;

final class AccountController
{
    public function index(): void
    {
        $user = Auth::requireUser();
        $usage = (new UsageLimiter())->snapshot((int) $user['id']);
        View::render('account/index', [
            'title' => 'Tu cuenta',
            'user' => $user,
            'usage' => $usage,
        ], 'layouts/app');
    }
}

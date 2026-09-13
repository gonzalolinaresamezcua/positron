<?php

declare(strict_types=1);

namespace Positron\Controllers;

use Positron\Core\View;
use Positron\Services\UsageLimiter;

final class HomeController
{
    public function index(): void
    {
        $limiter = new UsageLimiter();
        View::render('public/home', [
            'title' => 'POSITRON — chat de IA en órbita',
            'price' => $limiter->budgetEur() > 0 ? $limiter->budgetEur() : 12.0,
        ], 'layouts/public');
    }

    public function privacidad(): void
    {
        View::render('public/privacidad', ['title' => 'Privacidad'], 'layouts/public');
    }

    public function terminos(): void
    {
        View::render('public/terminos', ['title' => 'Términos'], 'layouts/public');
    }

    public function salud(): void
    {
        json_response(['ok' => true, 'app' => 'POSITRON', 'time' => now()]);
    }
}

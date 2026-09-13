<?php

declare(strict_types=1);

namespace Positrom\Controllers;

use Positrom\Core\View;

final class HomeController
{
    public function index(): void
    {
        View::render('public/home', [
            'title' => 'POSITROM — chat IA gratis y autoalojado',
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
        json_response(['ok' => true, 'app' => 'POSITROM', 'time' => now()]);
    }
}

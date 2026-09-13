<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\View;
use Positrom\Services\StatsService;
use Positrom\Services\UsageLimiter;

final class DashboardController
{
    public function index(): void
    {
        View::render('admin/dashboard', [
            'title' => 'Panel POSITROM',
            'stats' => (new StatsService())->dashboard(),
            'limiter' => (new UsageLimiter())->snapshot(0),
            'budget' => (new UsageLimiter())->budgetEur(),
        ], 'layouts/admin');
    }
}

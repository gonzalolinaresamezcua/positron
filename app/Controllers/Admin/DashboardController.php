<?php

declare(strict_types=1);

namespace Positron\Controllers\Admin;

use Positron\Core\View;
use Positron\Services\StatsService;
use Positron\Services\UsageLimiter;

final class DashboardController
{
    public function index(): void
    {
        View::render('admin/dashboard', [
            'title' => 'Panel POSITRON',
            'stats' => (new StatsService())->dashboard(),
            'limiter' => (new UsageLimiter())->snapshot(0),
            'budget' => (new UsageLimiter())->budgetEur(),
        ], 'layouts/admin');
    }
}

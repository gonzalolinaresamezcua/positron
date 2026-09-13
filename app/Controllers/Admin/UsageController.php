<?php

declare(strict_types=1);

namespace Positrom\Controllers\Admin;

use Positrom\Core\View;
use Positrom\Models\UsageEvent;
use Positrom\Services\UsageLimiter;

final class UsageController
{
    public function index(): void
    {
        $limiter = new UsageLimiter();
        View::render('admin/usage', [
            'title' => 'Uso y tokens',
            'month' => UsageEvent::globalTotals(period_ym()),
            'all' => UsageEvent::globalTotals(),
            'daily' => UsageEvent::daily(30),
            'top' => UsageEvent::topUsers(25),
            'config' => [
                'in' => $limiter->inputCostPer1M(),
                'out' => $limiter->outputCostPer1M(),
                'allowance' => $limiter->tokenAllowance(),
            ],
        ], 'layouts/admin');
    }
}

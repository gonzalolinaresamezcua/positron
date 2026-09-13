<?php

declare(strict_types=1);

namespace Positron\Controllers\Admin;

use Positron\Core\View;
use Positron\Models\UsageEvent;
use Positron\Services\UsageLimiter;

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
                'budget' => $limiter->budgetEur(),
                'in' => $limiter->inputCostPer1M(),
                'out' => $limiter->outputCostPer1M(),
                'allowance' => $limiter->tokenAllowance(),
            ],
        ], 'layouts/admin');
    }
}

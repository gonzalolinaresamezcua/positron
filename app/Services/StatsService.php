<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Models\UsageEvent;
use Positrom\Models\User;
use Positrom\Models\Visit;

final class StatsService
{
    public function dashboard(): array
    {
        $usageAll = UsageEvent::globalTotals();
        $usageMonth = UsageEvent::globalTotals(period_ym());
        return [
            'visits_all' => Visit::countAll(),
            'visits_today' => Visit::countSince(date('Y-m-d 00:00:00')),
            'visits_7d' => Visit::countSince(date('Y-m-d 00:00:00', strtotime('-6 days'))),
            'users' => User::count(),
            'users_admin' => User::countByRole('admin'),
            'users_clients' => User::countByRole('user'),
            'active_users' => User::countActive(),
            'requests_all' => (int) $usageAll['requests'],
            'requests_month' => (int) $usageMonth['requests'],
            'tokens_all' => (int) $usageAll['tokens_in'] + (int) $usageAll['tokens_out'],
            'tokens_month' => (int) $usageMonth['tokens_in'] + (int) $usageMonth['tokens_out'],
            'usage_cost_month' => (float) $usageMonth['cost_eur'],
            'visits_daily' => Visit::daily(14),
            'usage_daily' => UsageEvent::daily(14),
            'top_paths' => Visit::topPaths(),
            'top_users' => UsageEvent::topUsers(),
        ];
    }
}

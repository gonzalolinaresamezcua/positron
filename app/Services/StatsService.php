<?php

declare(strict_types=1);

namespace Positron\Services;

use Positron\Models\Payment;
use Positron\Models\UsageEvent;
use Positron\Models\User;
use Positron\Models\Visit;
use Positron\Core\Database;

final class StatsService
{
    public function dashboard(): array
    {
        $usageAll = UsageEvent::globalTotals();
        $usageMonth = UsageEvent::globalTotals(period_ym());
        $subs = Database::fetchAll(
            'SELECT status, COUNT(*) AS n FROM subscriptions GROUP BY status'
        );
        $byStatus = [];
        foreach ($subs as $row) {
            $byStatus[$row['status']] = (int) $row['n'];
        }
        return [
            'visits_all' => Visit::countAll(),
            'visits_today' => Visit::countSince(date('Y-m-d 00:00:00')),
            'visits_7d' => Visit::countSince(date('Y-m-d 00:00:00', strtotime('-6 days'))),
            'users' => User::count(),
            'users_admin' => User::countByRole('admin'),
            'users_clients' => User::countByRole('user'),
            'active_subs' => $byStatus['active'] ?? 0,
            'pending_subs' => $byStatus['pending_activation'] ?? 0,
            'past_due' => $byStatus['past_due'] ?? 0,
            'cancelled' => $byStatus['cancelled'] ?? 0,
            'payments_paid' => Payment::countPaid(),
            'revenue_eur' => Payment::sumPaid(),
            'requests_all' => (int) $usageAll['requests'],
            'requests_month' => (int) $usageMonth['requests'],
            'tokens_all' => (int) $usageAll['tokens_in'] + (int) $usageAll['tokens_out'],
            'tokens_month' => (int) $usageMonth['tokens_in'] + (int) $usageMonth['tokens_out'],
            'usage_cost_month' => (float) $usageMonth['cost_eur'],
            'visits_daily' => Visit::daily(14),
            'usage_daily' => UsageEvent::daily(14),
            'top_paths' => Visit::topPaths(),
            'top_users' => UsageEvent::topUsers(),
            'subs_by_status' => $byStatus,
        ];
    }
}

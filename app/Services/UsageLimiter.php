<?php

declare(strict_types=1);

namespace Positrom\Services;

use Positrom\Core\Config;
use Positrom\Core\Database;
use Positrom\Models\Setting;
use Positrom\Models\UsageEvent;

final class UsageLimiter
{
    public function inputCostPer1M(): float
    {
        $v = Setting::get('usage.token_input_cost_eur_per_1m');
        return $v !== null && $v !== '' ? (float) $v : (float) Config::get('usage.input_cost', 0.50);
    }

    public function outputCostPer1M(): float
    {
        $v = Setting::get('usage.token_output_cost_eur_per_1m');
        return $v !== null && $v !== '' ? (float) $v : (float) Config::get('usage.output_cost', 2.50);
    }

    public function tokenAllowance(): ?int
    {
        $v = Setting::get('usage.monthly_token_allowance');
        if ($v === null || $v === '') {
            $v = Config::get('usage.token_allowance', '');
        }
        if ($v === null || $v === '') {
            return null;
        }
        $n = (int) $v;
        return $n > 0 ? $n : null;
    }

    public function estimateCost(int $tokensIn, int $tokensOut): float
    {
        return ($tokensIn / 1_000_000) * $this->inputCostPer1M()
            + ($tokensOut / 1_000_000) * $this->outputCostPer1M();
    }

    public function snapshot(int $userId, ?string $periodYm = null): array
    {
        if ($userId <= 0) {
            return $this->emptySnapshot($periodYm);
        }
        $totals = UsageEvent::monthTotals($userId, $periodYm);
        $tokens = (int) $totals['tokens_in'] + (int) $totals['tokens_out'];
        $spent = (float) $totals['cost_eur'];
        $allowance = $this->tokenAllowance();
        $remainingTokens = $allowance !== null ? max(0, $allowance - $tokens) : null;
        $exhausted = $allowance !== null && $tokens >= $allowance;
        return [
            'period' => $periodYm ?? period_ym(),
            'tokens_in' => (int) $totals['tokens_in'],
            'tokens_out' => (int) $totals['tokens_out'],
            'tokens' => $tokens,
            'requests' => (int) $totals['requests'],
            'spent_eur' => $spent,
            'token_allowance' => $allowance,
            'remaining_tokens' => $remainingTokens,
            'exhausted' => $exhausted,
            'input_cost_per_1m' => $this->inputCostPer1M(),
            'output_cost_per_1m' => $this->outputCostPer1M(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptySnapshot(?string $periodYm): array
    {
        return [
            'period' => $periodYm ?? period_ym(),
            'tokens_in' => 0,
            'tokens_out' => 0,
            'tokens' => 0,
            'requests' => 0,
            'spent_eur' => 0.0,
            'token_allowance' => $this->tokenAllowance(),
            'remaining_tokens' => $this->tokenAllowance(),
            'exhausted' => false,
            'input_cost_per_1m' => $this->inputCostPer1M(),
            'output_cost_per_1m' => $this->outputCostPer1M(),
        ];
    }

    public function assertCanSpend(int $userId): array
    {
        $snap = $this->snapshot($userId);
        if ($snap['exhausted']) {
            throw new UsageExhaustedException(
                'Has alcanzado el tope mensual de tokens configurado por administración. El chat se reanuda el próximo mes o si se ajusta el cupo.'
            );
        }
        return $snap;
    }

    public function hitRateLimit(int $userId, int $maxPerMinute): bool
    {
        $window = date('Y-m-d H:i:00');
        $scope = 'chat:' . $userId;
        Database::query(
            'INSERT INTO rate_limits (scope, hits, window_start) VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            [$scope, $window]
        );
        $hits = (int) Database::value(
            'SELECT hits FROM rate_limits WHERE scope = ? AND window_start = ?',
            [$scope, $window]
        );
        return $hits > $maxPerMinute;
    }
}

final class UsageExhaustedException extends \RuntimeException
{
}

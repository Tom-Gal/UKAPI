<?php

namespace App\Domain\Usage;

use App\Models\ApiKey;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UsageMeter
{
    public function currentMonthlyUsage(ApiKey $apiKey): int
    {
        return $this->currentMonthlyUsageForUser($apiKey->user_id);
    }

    public function currentMonthlyUsageForUser(int $userId): int
    {
        return (int) DB::table('monthly_usage')->where('user_id', $userId)
            ->where('month', $this->periodStart()->toDateString())->value('requests');
    }

    /**
     * The conditional update takes a database write lock. The account total
     * and breakdown commit together; failures never fall back to a cache.
     */
    public function reserve(ApiKey $apiKey, string $endpointCode, int $quota, ?CarbonImmutable $at = null): ?int
    {
        $at ??= CarbonImmutable::now('UTC');
        $month = $this->periodStart($at)->toDateString();

        return DB::transaction(function () use ($apiKey, $endpointCode, $quota, $at, $month): ?int {
            DB::table('monthly_usage')->insertOrIgnore([
                'user_id' => $apiKey->user_id,
                'month' => $month,
                'requests' => 0,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $account = DB::table('monthly_usage')->where('user_id', $apiKey->user_id)->where('month', $month);

            if ((clone $account)->where('requests', '<', $quota)->increment('requests', 1, ['updated_at' => $at]) !== 1) {
                return null;
            }

            DB::table('monthly_endpoint_usage')->insertOrIgnore([
                'user_id' => $apiKey->user_id,
                'month' => $month,
                'endpoint' => $endpointCode,
                'requests' => 0,
            ]);
            DB::table('monthly_endpoint_usage')->where('user_id', $apiKey->user_id)
                ->where('month', $month)->where('endpoint', $endpointCode)->increment('requests');

            return (int) $account->value('requests');
        }, 5);
    }

    public function periodStart(?CarbonImmutable $at = null): CarbonImmutable
    {
        return ($at ?? CarbonImmutable::now('UTC'))->utc()->startOfMonth();
    }

    public function monthlyResetAt(?CarbonImmutable $at = null): int
    {
        return $this->periodStart($at)->addMonth()->getTimestamp();
    }

    /** @return list<array{endpoint: string, label: string, requests: int}> */
    public function currentMonthlyBreakdownForUser(int $userId): array
    {
        /** @var array<string, string> $labels */
        $labels = config('ukapi.usage_endpoint_labels', []);

        $rows = DB::table('monthly_endpoint_usage')->where('user_id', $userId)
            ->where('month', $this->periodStart()->toDateString())->where('requests', '>', 0)
            ->orderByDesc('requests')->orderBy('endpoint')->get();
        $breakdown = [];
        foreach ($rows as $row) {
            $endpoint = (string) $row->endpoint;
            $breakdown[] = [
                'endpoint' => $endpoint,
                'label' => $labels[$endpoint] ?? $endpoint,
                'requests' => (int) $row->requests,
            ];
        }

        return $breakdown;
    }
}

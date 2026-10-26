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
        return $this->monthlyUsageForUser($userId, $this->periodStart());
    }

    public function monthlyUsageForUser(int $userId, CarbonImmutable $month): int
    {
        return (int) DB::table('monthly_usage')->where('user_id', $userId)
            ->where('month', $this->periodStart($month)->toDateString())->value('requests');
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

            $date = $at->toDateString();
            DB::table('daily_usage')->insertOrIgnore([
                'user_id' => $apiKey->user_id,
                'date' => $date,
                'requests' => 0,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            DB::table('daily_usage')->where('user_id', $apiKey->user_id)
                ->where('date', $date)->increment('requests', 1, ['updated_at' => $at]);

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

    public function recordRejection(int $userId, string $reason, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now('UTC');
        $date = $at->toDateString();
        DB::table('daily_usage_rejections')->insertOrIgnore([
            'user_id' => $userId,
            'date' => $date,
            'reason' => $reason,
            'requests' => 0,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
        DB::table('daily_usage_rejections')->where('user_id', $userId)
            ->where('date', $date)->where('reason', $reason)
            ->increment('requests', 1, ['updated_at' => $at]);
    }

    /** @return list<array{date: string, requests: int, rate_limit_rejections: int, quota_rejections: int}> */
    public function dailyTrendForUser(int $userId, ?CarbonImmutable $month = null): array
    {
        $month = $this->periodStart($month);
        $today = CarbonImmutable::now('UTC')->startOfDay();
        $end = $month->isSameMonth($today) ? $today : $month->endOfMonth()->startOfDay();
        $usage = DB::table('daily_usage')->where('user_id', $userId)
            ->whereBetween('date', [$month->toDateString(), $end->toDateString()])
            ->pluck('requests', 'date');
        $rejections = DB::table('daily_usage_rejections')->where('user_id', $userId)
            ->whereBetween('date', [$month->toDateString(), $end->toDateString()])
            ->get()->groupBy(fn (object $row): string => (string) $row->date);
        $firstObservedDate = $usage->keys()->merge($rejections->keys())->sort()->first();
        if (! is_string($firstObservedDate)) {
            return [];
        }

        $start = CarbonImmutable::parse($firstObservedDate, 'UTC')->startOfDay();
        $trend = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            $dailyRejections = $rejections->get($dateString, collect());
            $trend[] = [
                'date' => $dateString,
                'requests' => (int) $usage->get($dateString, 0),
                'rate_limit_rejections' => (int) $dailyRejections->where('reason', 'rate_limit')->sum('requests'),
                'quota_rejections' => (int) $dailyRejections->where('reason', 'quota')->sum('requests'),
            ];
        }

        return $trend;
    }

    /** @return array{rate_limit: int, quota: int} */
    public function monthlyRejectionsForUser(int $userId, ?CarbonImmutable $month = null): array
    {
        $month = $this->periodStart($month);
        $rows = DB::table('daily_usage_rejections')->where('user_id', $userId)
            ->whereBetween('date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->selectRaw('reason, SUM(requests) AS requests')->groupBy('reason')->pluck('requests', 'reason');

        return [
            'rate_limit' => (int) $rows->get('rate_limit', 0),
            'quota' => (int) $rows->get('quota', 0),
        ];
    }

    /** @return list<array{endpoint: string, label: string, requests: int}> */
    public function currentMonthlyBreakdownForUser(int $userId): array
    {
        return $this->monthlyBreakdownForUser($userId, $this->periodStart());
    }

    /** @return list<array{endpoint: string, label: string, requests: int}> */
    public function monthlyBreakdownForUser(int $userId, CarbonImmutable $month): array
    {
        /** @var array<string, string> $labels */
        $labels = config('ukapi.usage_endpoint_labels', []);

        $rows = DB::table('monthly_endpoint_usage')->where('user_id', $userId)
            ->where('month', $this->periodStart($month)->toDateString())->where('requests', '>', 0)
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

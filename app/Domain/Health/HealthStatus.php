<?php

namespace App\Domain\Health;

use App\Models\ServiceHealthCheck;
use Carbon\CarbonImmutable;

final class HealthStatus
{
    public const SERVICES = [
        'application' => 'Application dependencies',
        'postcodes' => 'Postcode provider',
        'crime' => 'Crime provider',
        'flood' => 'Flood provider (England)',
        'companies' => 'Company provider',
        'sic' => 'SIC reference data',
    ];

    /** @return list<array<string, mixed>> */
    public function services(): array
    {
        try {
            $checks = ServiceHealthCheck::all()->keyBy('service');
        } catch (\Throwable) {
            // A missing migration or unreachable database must never produce green status.
            $checks = collect();
        }

        $services = [];
        foreach (self::SERVICES as $key => $name) {
            $check = $checks->get($key);
            $state = $this->state($check);
            $freshness = 'unknown';
            if ($key === 'application') {
                $freshness = 'not_applicable';
            } elseif ($key === 'crime' && $state === 'available' && $check?->source_date !== null) {
                $freshness = $check->source_date->lt(now()->subDays(max(1, (int) config('health.crime_source_max_age_days'))))
                    ? 'stale' : 'fresh';
            }
            $retrieval = 'unknown';
            if ($key === 'sic' && $state === 'available' && $check?->reference_retrieved_at !== null
                && ! $check->reference_retrieved_at->isFuture()) {
                $retrieval = $check->reference_retrieved_at->lt(now()->subDays(max(1, (int) config('health.sic_retrieval_max_age_days'))))
                    ? 'stale' : 'fresh';
            }
            $services[] = [
                'name' => $name,
                'state' => $state,
                'detail' => match ($state) {
                    'available' => 'Check passed',
                    'unavailable' => 'Check failed',
                    'stale' => 'Measurement overdue',
                    default => 'Not yet measured or check incomplete',
                },
                'last_attempt_at' => $check?->last_attempt_at?->toIso8601String(),
                'last_checked_at' => $check?->last_checked_at?->toIso8601String(),
                'last_success_at' => $check?->last_success_at?->toIso8601String(),
                'source_freshness' => $freshness,
                'source_date' => $check?->source_date?->format('Y-m'),
                'source_detail' => $key === 'crime'
                    ? 'Latest crime data month; freshness uses the configured age threshold.'
                    : ($key === 'application' ? 'No source dataset.' : 'These checks do not establish the source publication date.'),
                'reference_retrieved_at' => $check?->reference_retrieved_at?->toIso8601String(),
                'reference_freshness' => $retrieval,
            ];
        }

        return $services;
    }

    private function state(?ServiceHealthCheck $check): string
    {
        $threshold = CarbonImmutable::now()->subSeconds(max(60, (int) config('health.stale_after_seconds')));
        if ($check?->last_attempt_at === null) {
            return 'unknown';
        }
        if ($check->last_attempt_at->lt($threshold)) {
            return 'stale';
        }
        if ($check->status === 'unknown' || $check->last_checked_at === null) {
            return 'unknown';
        }
        if ($check->last_checked_at->lt($threshold)) {
            return 'stale';
        }

        return in_array($check->status, ['available', 'unavailable'], true) ? $check->status : 'unknown';
    }
}

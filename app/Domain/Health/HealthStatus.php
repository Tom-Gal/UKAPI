<?php

namespace App\Domain\Health;

use App\Models\ServiceHealthCheck;
use App\Models\ServiceHealthIncident;
use Carbon\CarbonImmutable;

final class HealthStatus
{
    public const SERVICES = [
        'application' => 'UKApi.co.uk platform',
        'postcodes' => 'Postcodes.io',
        'crime' => 'Police.uk',
        'flood' => 'Environment Agency flood monitoring',
        'companies' => 'Companies House',
        'mot' => 'DVSA MOT History',
        'sic' => 'Companies House SIC 2007',
        'bank_holidays' => 'GOV.UK bank holidays',
        'food_hygiene' => 'Food Standards Agency ratings',
        'planning_data' => 'Planning Data',
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
                'code' => $key,
                'name' => $name,
                'description' => $this->serviceDescription($key),
                'state' => $state,
                'status_label' => match ($state) {
                    'available' => 'Operational',
                    'unavailable' => 'Service disruption',
                    'stale' => 'Monitoring delayed',
                    default => 'Awaiting first check',
                },
                'detail' => match (true) {
                    $state === 'unavailable' => $this->failureDetail($check?->failure_reason),
                    $state === 'stale' => 'The latest scheduled check is overdue.',
                    $state === 'unknown' => 'No completed check is available yet.',
                    default => null,
                },
                'impact' => $state === 'unavailable' ? $this->serviceImpact($key) : null,
                'consecutive_failures' => $check->consecutive_failures ?? 0,
                'latency_ms' => $check?->latency_ms,
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

    /** @return list<array<string, mixed>> */
    public function incidents(): array
    {
        try {
            $incidents = ServiceHealthIncident::query()
                ->where(function ($query): void {
                    $query->whereNull('recovered_at')
                        ->orWhere('recovered_at', '>=', now()->subDays(14));
                })
                ->latest('started_at')
                ->limit(20)
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $publicIncidents = [];
        foreach ($incidents as $incident) {
            $name = self::SERVICES[$incident->service] ?? 'Monitored service';

            $publicIncidents[] = [
                'service' => $incident->service,
                'name' => $name,
                'state' => $incident->recovered_at === null ? 'open' : 'resolved',
                'title' => $name.' disruption',
                'detail' => $this->failureDetail($incident->failure_reason),
                'impact' => $this->serviceImpact($incident->service),
                'started_at' => $incident->started_at->toIso8601String(),
                'last_observed_at' => $incident->last_observed_at->toIso8601String(),
                'recovered_at' => $incident->recovered_at?->toIso8601String(),
                'occurrences' => $incident->occurrences,
            ];
        }

        return $publicIncidents;
    }

    private function failureDetail(?string $reason): string
    {
        return match ($reason) {
            'configuration_missing' => 'Required provider configuration is missing.',
            'credentials_rejected' => 'The source rejected UKApi.co.uk credentials.',
            'token_unavailable' => 'The MOT authentication service could not be reached.',
            'token_failure' => 'The MOT authentication service returned an invalid response.',
            'api_unavailable' => 'The source did not respond successfully.',
            'invalid_response' => 'The source returned data that could not be validated.',
            'probe_capacity' => 'The check could not run because the source request limit was reached.',
            default => 'The automated check did not complete.',
        };
    }

    private function serviceDescription(string $service): string
    {
        return match ($service) {
            'application' => 'API, dashboard, database and cache',
            'postcodes' => 'Postcode lookup and validation',
            'crime' => 'Street crime, outcomes and categories',
            'flood' => 'Flood warnings, stations and readings',
            'companies' => 'Company search, profiles and officers',
            'mot' => 'MOT history and vehicle recalls',
            'sic' => 'SIC code search, lookup and export',
            'bank_holidays' => 'Bank-holiday calendars',
            'food_hygiene' => 'Food establishment ratings',
            'planning_data' => 'Planning constraints by location',
            default => 'UKApi.co.uk service',
        };
    }

    private function serviceImpact(string $service): string
    {
        return match ($service) {
            'application' => 'API requests and account access may fail.',
            'postcodes' => 'Postcode endpoints may return an upstream unavailable error.',
            'crime' => 'Crime endpoints may return an upstream unavailable error.',
            'flood' => 'Flood endpoints may return an upstream unavailable error. Do not rely on UKApi.co.uk for emergency alerts.',
            'companies' => 'Company endpoints may return an upstream unavailable error.',
            'mot' => 'MOT endpoints may return an upstream unavailable error.',
            'sic' => 'SIC endpoints may be unavailable.',
            'bank_holidays' => 'Bank-holiday endpoints may return an upstream unavailable error.',
            'food_hygiene' => 'Food hygiene endpoints may return an upstream unavailable error.',
            'planning_data' => 'Planning constraint endpoints may return an upstream unavailable error.',
            default => 'Requests using this service may fail.',
        };
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

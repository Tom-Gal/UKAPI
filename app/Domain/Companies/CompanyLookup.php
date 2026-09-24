<?php

namespace App\Domain\Companies;

use App\Integrations\Contracts\CompanyProvider;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class CompanyLookup
{
    public function __construct(
        private readonly CompanyProvider $provider,
        private readonly Repository $cache,
    ) {}

    public function find(CompanyNumber $companyNumber): CompanyProfile
    {
        $entry = $this->cachedPayload(
            key: 'ukapi:v1:companies:profile:'.$companyNumber->value(),
            freshMinutes: (int) config('ukapi.companies_house.profile_cache_ttl_minutes'),
            fetch: fn (): array => $this->provider->find($companyNumber)->toArray(),
        );

        try {
            return new CompanyProfile(
                CompanyData::fromArray($entry['data']),
                $entry['cached'],
                $entry['stale'],
            );
        } catch (InvalidArgumentException) {
            $this->cache->forget('ukapi:v1:companies:profile:'.$companyNumber->value());

            throw ApiException::providerInvalidResponse();
        }
    }

    public function search(string $query, int $page, int $perPage): CompanySearchResults
    {
        $entry = $this->cachedPayload(
            key: sprintf('ukapi:v1:companies:search:%s:%d:%d', hash('sha256', $query), $page, $perPage),
            freshMinutes: (int) config('ukapi.companies_house.search_cache_ttl_minutes'),
            fetch: fn (): array => $this->provider->search($query, $page, $perPage)->toCacheArray(),
        );

        try {
            return CompanySearchResults::fromArray($entry['data'], $entry['cached'], $entry['stale']);
        } catch (InvalidArgumentException) {
            $this->cache->forget(sprintf('ukapi:v1:companies:search:%s:%d:%d', hash('sha256', $query), $page, $perPage));

            throw ApiException::providerInvalidResponse();
        }
    }

    public function officers(CompanyNumber $companyNumber, int $page, int $perPage): CompanyOfficers
    {
        $key = sprintf('ukapi:v1:companies:officers:%s:%d:%d', $companyNumber->value(), $page, $perPage);
        $entry = $this->cachedPayload(
            key: $key,
            freshMinutes: (int) config('ukapi.companies_house.officers_cache_ttl_minutes'),
            fetch: fn (): array => $this->provider->officers($companyNumber, $page, $perPage)->toCacheArray(),
        );

        try {
            return CompanyOfficers::fromArray($entry['data'], $entry['cached'], $entry['stale']);
        } catch (InvalidArgumentException) {
            $this->cache->forget($key);

            throw ApiException::providerInvalidResponse();
        }
    }

    public function filings(CompanyNumber $companyNumber, int $page, int $perPage): CompanyFilings
    {
        $key = sprintf('ukapi:v1:companies:filings:%s:%d:%d', $companyNumber->value(), $page, $perPage);
        $entry = $this->cachedPayload(
            key: $key,
            freshMinutes: (int) config('ukapi.companies_house.filings_cache_ttl_minutes'),
            fetch: fn (): array => $this->provider->filings($companyNumber, $page, $perPage)->toCacheArray(),
        );

        try {
            return CompanyFilings::fromArray($entry['data'], $entry['cached'], $entry['stale']);
        } catch (InvalidArgumentException) {
            $this->cache->forget($key);

            throw ApiException::providerInvalidResponse();
        }
    }

    /**
     * @param  Closure(): array<string, mixed>  $fetch
     * @return array{data: array<string, mixed>, cached: bool, stale: bool}
     */
    private function cachedPayload(string $key, int $freshMinutes, Closure $fetch): array
    {
        $cached = $this->cache->get($key);
        $now = CarbonImmutable::now();
        $staleData = null;

        if (is_array($cached) && ($cached['state'] ?? null) === 'not_found') {
            throw ApiException::companyNotFound();
        }

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            $freshUntil = $this->timestamp($cached['fresh_until'] ?? null);
            $staleUntil = $this->timestamp($cached['stale_until'] ?? null);

            if ($freshUntil !== null && $freshUntil->isFuture()) {
                return ['data' => $cached['data'], 'cached' => true, 'stale' => false];
            }

            if ($staleUntil !== null && $staleUntil->isFuture()) {
                $staleData = $cached['data'];
            }
        }

        try {
            $data = $fetch();
        } catch (ApiException $exception) {
            if ($exception->apiCode === 'company_not_found') {
                $this->cache->put($key, ['state' => 'not_found'], $this->negativeTtl());
            }

            if ($staleData !== null && str_starts_with($exception->apiCode, 'upstream_')) {
                return ['data' => $staleData, 'cached' => true, 'stale' => true];
            }

            throw $exception;
        }

        $freshUntil = $now->addMinutes(max(1, $freshMinutes));
        $staleUntil = $freshUntil->addMinutes(max(1, (int) config('ukapi.companies_house.stale_cache_ttl_minutes')));
        $this->cache->put($key, [
            'state' => 'found',
            'data' => $data,
            'fresh_until' => $freshUntil->toIso8601String(),
            'stale_until' => $staleUntil->toIso8601String(),
        ], $staleUntil);

        return ['data' => $data, 'cached' => false, 'stale' => false];
    }

    private function negativeTtl(): \DateTimeInterface
    {
        return now()->addMinutes(max(1, (int) config('ukapi.companies_house.negative_cache_ttl_minutes')));
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

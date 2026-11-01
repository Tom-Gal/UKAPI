<?php

namespace App\Domain\Planning;

use App\Domain\Geography\GeographyService;
use App\Integrations\Contracts\PlanningDataProvider;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class PlanningConstraintService
{
    public function __construct(
        private readonly PlanningDataProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
    ) {}

    public function constraints(PlanningConstraintQuery $query): PlanningConstraintCollection
    {
        if ($query->postcode !== null) {
            $lookup = $this->geography->lookup($query->postcode);
            $latitude = $lookup->postcode->latitude;
            $longitude = $lookup->postcode->longitude;

            if ($latitude === null || $longitude === null) {
                throw ApiException::coordinatesNotFound();
            }

            $query = $query->atCoordinates($latitude, $longitude);
        }

        $key = 'ukapi:v1:planning:constraints:'.hash('sha256', json_encode(
            $query->cacheData(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return $this->cachedCollection(
            key: $key,
            fetch: fn (): PlanningConstraintCollection => $this->provider->constraints($query),
        );
    }

    private function cachedCollection(string $key, Closure $fetch): PlanningConstraintCollection
    {
        $cached = $this->cache->get($key);
        $staleData = null;

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            $freshUntil = $this->timestamp($cached['fresh_until'] ?? null);
            $staleUntil = $this->timestamp($cached['stale_until'] ?? null);

            if ($freshUntil?->isFuture()) {
                try {
                    return PlanningConstraintCollection::fromArray($cached['data'], true, false);
                } catch (InvalidArgumentException) {
                    $this->cache->forget($key);
                }
            }

            if ($staleUntil?->isFuture()) {
                $staleData = $cached['data'];
            }
        }

        try {
            $result = $fetch();
        } catch (ApiException $exception) {
            if ($staleData !== null && $exception->apiCode === 'upstream_unavailable') {
                try {
                    return PlanningConstraintCollection::fromArray($staleData, true, true);
                } catch (InvalidArgumentException) {
                    $this->cache->forget($key);
                }
            }

            throw $exception;
        }

        $freshUntil = CarbonImmutable::now()->addHours(max(1, (int) config('ukapi.planning_data.cache_ttl_hours')));
        $staleUntil = $freshUntil->addHours(max(1, (int) config('ukapi.planning_data.stale_cache_ttl_hours')));
        $this->cache->put($key, [
            'state' => 'found',
            'data' => $result->toCacheArray(),
            'fresh_until' => $freshUntil->toIso8601String(),
            'stale_until' => $staleUntil->toIso8601String(),
        ], $staleUntil);

        return $result;
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

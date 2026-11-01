<?php

namespace App\Domain\Food;

use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkPostcode;
use App\Integrations\Contracts\FoodHygieneProvider;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class FoodEstablishmentService
{
    public function __construct(
        private readonly FoodHygieneProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
    ) {}

    public function find(FoodEstablishmentId $id): FoodEstablishmentLookup
    {
        $key = 'ukapi:v1:food:establishment:'.$id->value();
        $cached = $this->cache->get($key);

        if (is_array($cached) && ($cached['state'] ?? null) === 'not_found') {
            throw ApiException::foodEstablishmentNotFound();
        }

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            try {
                return new FoodEstablishmentLookup(FoodEstablishmentData::fromArray($cached['data']), true, false);
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        try {
            $establishment = $this->provider->find($id);
        } catch (ApiException $exception) {
            if ($exception->apiCode === 'food_establishment_not_found') {
                $this->cache->put($key, ['state' => 'not_found'], $this->negativeTtl());
            }

            throw $exception;
        }

        $this->cache->put($key, ['state' => 'found', 'data' => $establishment->toArray()], $this->freshTtl());

        return new FoodEstablishmentLookup($establishment, false, false);
    }

    public function search(FoodEstablishmentSearch $search): FoodEstablishmentCollection
    {
        $key = $this->searchKey($search);

        return $this->cachedCollection(
            key: $key,
            fetch: fn (): FoodEstablishmentCollection => $this->provider->search($search),
        );
    }

    public function nearby(
        UkPostcode $postcode,
        int $radiusKilometres,
        ?string $schemeType,
        ?string $rating,
        ?string $ratingOperator,
        int $page,
        int $perPage,
    ): FoodEstablishmentCollection {
        $lookup = $this->geography->lookup($postcode);
        $latitude = $lookup->postcode->latitude;
        $longitude = $lookup->postcode->longitude;

        if ($latitude === null || $longitude === null) {
            throw ApiException::coordinatesNotFound();
        }

        return $this->search(FoodEstablishmentSearch::nearby(
            postcode: $postcode,
            latitude: $latitude,
            longitude: $longitude,
            radiusKilometres: $radiusKilometres,
            schemeType: $schemeType,
            rating: $rating,
            ratingOperator: $ratingOperator,
            page: $page,
            perPage: $perPage,
        ));
    }

    private function cachedCollection(string $key, Closure $fetch): FoodEstablishmentCollection
    {
        $cached = $this->cache->get($key);
        $staleData = null;

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            $freshUntil = $this->timestamp($cached['fresh_until'] ?? null);
            $staleUntil = $this->timestamp($cached['stale_until'] ?? null);

            if ($freshUntil?->isFuture()) {
                try {
                    return FoodEstablishmentCollection::fromArray($cached['data'], true, false);
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
            if ($staleData !== null && str_starts_with($exception->apiCode, 'upstream_')) {
                try {
                    return FoodEstablishmentCollection::fromArray($staleData, true, true);
                } catch (InvalidArgumentException) {
                    $this->cache->forget($key);
                }
            }

            throw $exception;
        }

        $freshUntil = CarbonImmutable::now()->addHours(max(1, (int) config('ukapi.food_hygiene.cache_ttl_hours')));
        $staleUntil = $freshUntil->addHours(max(1, (int) config('ukapi.food_hygiene.stale_cache_ttl_hours')));
        $this->cache->put($key, [
            'state' => 'found',
            'data' => $result->toCacheArray(),
            'fresh_until' => $freshUntil->toIso8601String(),
            'stale_until' => $staleUntil->toIso8601String(),
        ], $staleUntil);

        return $result;
    }

    private function searchKey(FoodEstablishmentSearch $search): string
    {
        return 'ukapi:v1:food:search:'.hash('sha256', json_encode(
            $search->cacheData(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function freshTtl(): \DateTimeInterface
    {
        return now()->addHours(max(1, (int) config('ukapi.food_hygiene.cache_ttl_hours')));
    }

    private function negativeTtl(): \DateTimeInterface
    {
        return now()->addMinutes(max(1, (int) config('ukapi.food_hygiene.negative_cache_ttl_minutes')));
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

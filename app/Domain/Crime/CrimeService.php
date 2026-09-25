<?php

namespace App\Domain\Crime;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkPostcode;
use App\Integrations\Contracts\CrimeProvider;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class CrimeService
{
    public function __construct(
        private readonly CrimeProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
    ) {}

    public function nearby(UkPostcode $postcode, ?YearMonth $month): CrimeResults
    {
        $key = sprintf('ukapi:v1:crime:nearby:%s:%s', $postcode->compact(), $month?->value() ?? 'latest');
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new CrimeResults($this->crimeList($cached['data']), true);
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $geography = $this->geography->lookup($postcode);
        $latitude = $geography->postcode->latitude;
        $longitude = $geography->postcode->longitude;

        if ($latitude === null || $longitude === null) {
            throw ApiException::coordinatesNotFound();
        }

        $records = $this->provider->nearby(
            Coordinates::from((string) $latitude, (string) $longitude),
            $month,
        );
        $this->cache->put($key, [
            'data' => array_map(static fn (CrimeData $crime): array => $crime->toArray(), $records),
        ], $this->crimeTtl());

        return new CrimeResults($records, false);
    }

    public function categories(?YearMonth $month): CrimeCategories
    {
        $key = 'ukapi:v1:crime:categories:'.($month?->value() ?? 'latest');
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new CrimeCategories($this->categoryList($cached['data']), true);
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $categories = $this->provider->categories($month);
        $this->cache->put($key, [
            'data' => array_map(static fn (CrimeCategory $category): array => $category->toArray(), $categories),
        ], $this->categoriesTtl());

        return new CrimeCategories($categories, false);
    }

    /** @param array<int, mixed> $data
     * @return list<CrimeData>
     */
    private function crimeList(array $data): array
    {
        $records = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached crime entry is invalid.');
            }

            $records[] = CrimeData::fromArray($item);
        }

        return $records;
    }

    /** @param array<int, mixed> $data
     * @return list<CrimeCategory>
     */
    private function categoryList(array $data): array
    {
        $categories = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached crime category is invalid.');
            }

            $categories[] = CrimeCategory::fromArray($item);
        }

        return $categories;
    }

    private function crimeTtl(): \DateTimeInterface
    {
        return now()->addHours(max(1, (int) config('ukapi.police_uk.crime_cache_ttl_hours')));
    }

    private function categoriesTtl(): \DateTimeInterface
    {
        return now()->addHours(max(1, (int) config('ukapi.police_uk.categories_cache_ttl_hours')));
    }
}

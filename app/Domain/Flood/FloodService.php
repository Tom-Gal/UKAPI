<?php

namespace App\Domain\Flood;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkPostcode;
use App\Integrations\Contracts\FloodProvider;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class FloodService
{
    public function __construct(
        private readonly FloodProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
    ) {}

    public function warnings(): FloodWarnings
    {
        $key = 'ukapi:v1:flood:warnings';
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new FloodWarnings(
                    $this->warningsFromCache($cached['data']),
                    true,
                    $this->retrievedAt($cached),
                    $this->sourceUpdatedAt($cached),
                );
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $warnings = $this->provider->warnings();

        return $this->cacheWarnings($key, $warnings);
    }

    public function warningsNear(UkPostcode $postcode): FloodWarnings
    {
        $coordinates = $this->coordinatesFor($postcode);
        $key = 'ukapi:v1:flood:nearby:'.$postcode->compact();
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new FloodWarnings(
                    $this->warningsFromCache($cached['data']),
                    true,
                    $this->retrievedAt($cached),
                    $this->sourceUpdatedAt($cached),
                );
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $warnings = $this->provider->warnings($coordinates);

        return $this->cacheWarnings($key, $warnings);
    }

    public function stationsNear(UkPostcode $postcode): FloodStations
    {
        $coordinates = $this->coordinatesFor($postcode);
        $key = 'ukapi:v1:flood:stations:'.$postcode->compact();
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new FloodStations($this->stationsFromCache($cached['data']), true, $this->retrievedAt($cached));
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $stations = $this->provider->stationsNear($coordinates);
        $retrievedAt = now()->toIso8601String();
        $this->cache->put($key, [
            'data' => array_map(static fn (FloodStation $station): array => $station->toArray(), $stations),
            'retrieved_at' => $retrievedAt,
        ], $this->ttl());

        return new FloodStations($stations, false, $retrievedAt);
    }

    public function readings(FloodStationReference $station): FloodReadings
    {
        $key = 'ukapi:v1:flood:readings:'.$station->value();
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                return new FloodReadings(
                    $this->readingsFromCache($cached['data']),
                    true,
                    $this->retrievedAt($cached),
                    $this->sourceUpdatedAt($cached),
                );
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $readings = $this->provider->readings($station);
        $retrievedAt = now()->toIso8601String();
        $sourceUpdatedAt = $this->latestReadingAt($readings);
        $this->cache->put($key, [
            'data' => array_map(static fn (FloodReading $reading): array => $reading->toArray(), $readings),
            'retrieved_at' => $retrievedAt,
            'source_updated_at' => $sourceUpdatedAt,
        ], $this->ttl());

        return new FloodReadings($readings, false, $retrievedAt, $sourceUpdatedAt);
    }

    public function nearbyDistanceKilometres(): int
    {
        return max(1, min(100, (int) config('ukapi.environment_agency.nearby_distance_kilometres')));
    }

    /** @param list<FloodWarning> $warnings */
    private function cacheWarnings(string $key, array $warnings): FloodWarnings
    {
        $retrievedAt = now()->toIso8601String();
        $sourceUpdatedAt = $this->latestWarningAt($warnings);
        $this->cache->put($key, [
            'data' => array_map(static fn (FloodWarning $warning): array => $warning->toArray(), $warnings),
            'retrieved_at' => $retrievedAt,
            'source_updated_at' => $sourceUpdatedAt,
        ], $this->ttl());

        return new FloodWarnings($warnings, false, $retrievedAt, $sourceUpdatedAt);
    }

    private function coordinatesFor(UkPostcode $postcode): Coordinates
    {
        $geography = $this->geography->lookup($postcode);
        $latitude = $geography->postcode->latitude;
        $longitude = $geography->postcode->longitude;

        if ($latitude === null || $longitude === null) {
            throw ApiException::coordinatesNotFound();
        }

        return Coordinates::from((string) $latitude, (string) $longitude);
    }

    /** @param array<int, mixed> $data
     * @return list<FloodWarning>
     */
    private function warningsFromCache(array $data): array
    {
        $warnings = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached flood warning is invalid.');
            }

            $warnings[] = FloodWarning::fromArray($item);
        }

        return $warnings;
    }

    /** @param array<int, mixed> $data
     * @return list<FloodStation>
     */
    private function stationsFromCache(array $data): array
    {
        $stations = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached flood station is invalid.');
            }

            $stations[] = FloodStation::fromArray($item);
        }

        return $stations;
    }

    /** @param array<int, mixed> $data
     * @return list<FloodReading>
     */
    private function readingsFromCache(array $data): array
    {
        $readings = [];

        foreach ($data as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Cached flood reading is invalid.');
            }

            $readings[] = FloodReading::fromArray($item);
        }

        return $readings;
    }

    /** @param list<FloodWarning> $warnings */
    private function latestWarningAt(array $warnings): ?string
    {
        $timestamps = array_filter(array_map(
            static fn (FloodWarning $warning): ?string => $warning->sourceUpdatedAt(),
            $warnings,
        ));

        return $timestamps === [] ? null : max($timestamps);
    }

    /** @param list<FloodReading> $readings */
    private function latestReadingAt(array $readings): ?string
    {
        $timestamps = array_map(static fn (FloodReading $reading): string => $reading->recordedAt, $readings);

        return $timestamps === [] ? null : max($timestamps);
    }

    /** @param array<string, mixed> $entry */
    private function retrievedAt(array $entry): string
    {
        return is_string($entry['retrieved_at'] ?? null) ? $entry['retrieved_at'] : now()->toIso8601String();
    }

    /** @param array<string, mixed> $entry */
    private function sourceUpdatedAt(array $entry): ?string
    {
        return is_string($entry['source_updated_at'] ?? null) ? $entry['source_updated_at'] : null;
    }

    private function ttl(): \DateTimeInterface
    {
        return now()->addMinutes(max(1, (int) config('ukapi.environment_agency.cache_ttl_minutes')));
    }
}

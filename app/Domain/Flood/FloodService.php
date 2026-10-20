<?php

namespace App\Domain\Flood;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkPostcode;
use App\Domain\Snapshots\SnapshotStore;
use App\Integrations\Contracts\FloodProvider;
use App\Models\DatasetSnapshot;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class FloodService
{
    private const READINGS_SNAPSHOT_DATASET = 'environment_agency.flood.readings';

    public function __construct(
        private readonly FloodProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
        private readonly SnapshotStore $snapshots,
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
                    ...$this->snapshotMetadata($station, $this->readingsFromCache($cached['data'])),
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

        $snapshot = $this->captureReadingSnapshot($station, $readings);

        return new FloodReadings(
            $readings,
            false,
            $retrievedAt,
            $sourceUpdatedAt,
            $snapshot->version,
            $snapshot->checksum,
            $snapshot->captured_at?->toISOString(),
        );
    }

    /**
     * @return array{
     *     since: string,
     *     until: string,
     *     from: DatasetSnapshot,
     *     to: DatasetSnapshot,
     *     diff: array<string, mixed>
     * }
     */
    public function readingChanges(FloodStationReference $station, string $since, ?string $until = null): array
    {
        $this->readings($station);
        $from = $this->snapshots->find(self::READINGS_SNAPSHOT_DATASET, $since);
        $to = $until === null
            ? $this->snapshots->active(self::READINGS_SNAPSHOT_DATASET, $station->value())
            : $this->snapshots->find(self::READINGS_SNAPSHOT_DATASET, $until);

        if ($from === null) {
            throw ApiException::invalidParameter('since', 'The requested flood readings snapshot version was not found.');
        }

        if ($to === null) {
            throw ApiException::invalidParameter('until', 'The requested flood readings snapshot version was not found.');
        }

        return [
            'since' => $from->version,
            'until' => $to->version,
            'from' => $from,
            'to' => $to,
            'diff' => $this->snapshots->diff(
                $from,
                $to,
                static fn (array $record): string => sprintf(
                    '%s|%s',
                    (string) ($record['measure_id'] ?? ''),
                    (string) ($record['recorded_at'] ?? ''),
                ),
            ),
        ];
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

    /** @param list<FloodReading> $readings */
    private function captureReadingSnapshot(FloodStationReference $station, array $readings): DatasetSnapshot
    {
        $payload = array_map(static fn (FloodReading $reading): array => $reading->toArray(), $readings);
        usort($payload, static fn (array $left, array $right): int => strcmp(
            sprintf('%s|%s', $left['measure_id'], $left['recorded_at']),
            sprintf('%s|%s', $right['measure_id'], $right['recorded_at']),
        ));
        $sourceUpdatedAt = $this->latestReadingAt($readings);

        return $this->snapshots->captureIfChanged(
            self::READINGS_SNAPSHOT_DATASET,
            $station->value(),
            'environment_agency_flood_monitoring',
            $payload,
            $sourceUpdatedAt === null ? null : CarbonImmutable::parse($sourceUpdatedAt),
        );
    }

    /**
     * @param  list<FloodReading>  $readings
     * @return array{0: string, 1: string, 2: ?string}
     */
    private function snapshotMetadata(FloodStationReference $station, array $readings): array
    {
        $snapshot = $this->captureReadingSnapshot($station, $readings);

        return [
            $snapshot->version,
            $snapshot->checksum,
            $snapshot->captured_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Domain\Crime;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkPostcode;
use App\Domain\Snapshots\SnapshotStore;
use App\Integrations\Contracts\CrimeProvider;
use App\Models\DatasetSnapshot;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class CrimeService
{
    private const SNAPSHOT_DATASET = 'police_uk.crime.nearby';

    public function __construct(
        private readonly CrimeProvider $provider,
        private readonly GeographyService $geography,
        private readonly Repository $cache,
        private readonly SnapshotStore $snapshots,
    ) {}

    public function nearby(UkPostcode $postcode, ?YearMonth $month): CrimeResults
    {
        $snapshot = $month === null ? null : $this->snapshots->active(
            self::SNAPSHOT_DATASET,
            $this->snapshotScope($postcode, $month),
        );

        if ($snapshot !== null) {
            return $this->snapshotResults($snapshot, true);
        }

        $key = sprintf('ukapi:v1:crime:nearby:%s:%s', $postcode->compact(), $month?->value() ?? 'latest');
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['data'] ?? null)) {
            try {
                $records = $this->crimeList($cached['data']);

                return $month === null
                    ? new CrimeResults($records, true)
                    : $this->captureSnapshot($postcode, $month, $records, true);
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

        return $month === null
            ? new CrimeResults($records, false)
            : $this->captureSnapshot($postcode, $month, $records, false);
    }

    /**
     * Compare two immutable Police.uk monthly snapshots for a postcode.
     *
     * @return array{
     *     since: string,
     *     until: string,
     *     from: DatasetSnapshot,
     *     to: DatasetSnapshot,
     *     diff: array<string, mixed>
     * }
     */
    public function changes(UkPostcode $postcode, YearMonth $since, ?YearMonth $until): array
    {
        $until ??= YearMonth::from(now()->subMonth()->format('Y-m'));

        if ($until === null) {
            throw ApiException::providerUnavailable();
        }

        if ($until->value() < $since->value()) {
            throw ApiException::invalidParameter('until', 'The until month must not be earlier than since.');
        }

        $this->nearby($postcode, $since);
        $this->nearby($postcode, $until);
        $from = $this->snapshots->active(self::SNAPSHOT_DATASET, $this->snapshotScope($postcode, $since));
        $to = $this->snapshots->active(self::SNAPSHOT_DATASET, $this->snapshotScope($postcode, $until));

        if ($from === null || $to === null) {
            throw ApiException::providerUnavailable();
        }

        return [
            'since' => $since->value(),
            'until' => $until->value(),
            'from' => $from,
            'to' => $to,
            'diff' => $this->snapshots->diff(
                $from,
                $to,
                fn (array $record): string => $this->crimeIdentity($record),
            ),
        ];
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

    /** @param list<CrimeData> $records */
    private function captureSnapshot(UkPostcode $postcode, YearMonth $month, array $records, bool $cached): CrimeResults
    {
        $payload = array_map(static fn (CrimeData $crime): array => $crime->toArray(), $records);
        usort($payload, fn (array $left, array $right): int => strcmp(
            $this->crimeIdentity($left),
            $this->crimeIdentity($right),
        ));

        $snapshot = $this->snapshots->capture(
            self::SNAPSHOT_DATASET,
            $this->snapshotScope($postcode, $month),
            'police_uk',
            $payload,
        );

        return new CrimeResults(
            $this->crimeList($payload),
            $cached,
            $snapshot->version,
            $snapshot->checksum,
            $snapshot->captured_at?->toISOString(),
        );
    }

    private function snapshotResults(DatasetSnapshot $snapshot, bool $cached): CrimeResults
    {
        return new CrimeResults(
            $this->crimeList($this->snapshots->payload($snapshot)),
            $cached,
            $snapshot->version,
            $snapshot->checksum,
            $snapshot->captured_at?->toISOString(),
        );
    }

    private function snapshotScope(UkPostcode $postcode, YearMonth $month): string
    {
        return $postcode->compact().'|'.$month->value();
    }

    /** @param array<string, mixed> $record */
    private function crimeIdentity(array $record): string
    {
        $persistentId = $record['persistent_id'] ?? null;

        if (is_string($persistentId) && $persistentId !== '') {
            return 'persistent:'.$persistentId;
        }

        return 'fingerprint:'.hash('sha256', json_encode(
            $record,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
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

<?php

namespace App\Domain\Companies;

use App\Domain\Snapshots\SnapshotStore;
use App\Models\DatasetSnapshot;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;

final class SicLookup
{
    private const SNAPSHOT_DATASET = 'companies_house.sic_2007';

    private const SNAPSHOT_SCOPE = 'reference';

    public function __construct(
        private readonly SicReference $reference,
        private readonly Repository $cache,
        private readonly SnapshotStore $snapshots,
    ) {}

    /** @return array{sic: SicData, snapshot: SicSnapshot, snapshot_version: string, cached: bool} */
    public function find(SicCode $code): array
    {
        [$snapshot, $durableSnapshot] = $this->currentSnapshots();
        $key = sprintf('ukapi:v1:sic:%s:%s', $snapshot->checksum, $code->value());
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            try {
                return [
                    'sic' => SicData::fromArray($cached),
                    'snapshot' => $snapshot,
                    'snapshot_version' => $durableSnapshot->version,
                    'cached' => true,
                ];
            } catch (\InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $sic = $snapshot->find($code);
        if ($sic === null) {
            throw ApiException::sicNotFound();
        }

        $this->cache->put($key, $sic->toArray(), $this->ttl());

        return [
            'sic' => $sic,
            'snapshot' => $snapshot,
            'snapshot_version' => $durableSnapshot->version,
            'cached' => false,
        ];
    }

    /** @return array{records: list<SicData>, total: int, snapshot: SicSnapshot, snapshot_version: string, cached: bool} */
    public function search(string $query, int $page, int $perPage): array
    {
        [$snapshot, $durableSnapshot] = $this->currentSnapshots();
        $key = sprintf('ukapi:v1:sic:search:%s:%s:%d:%d', $snapshot->checksum, hash('sha256', mb_strtolower($query)), $page, $perPage);
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['items'] ?? null) && is_int($cached['total'] ?? null)) {
            try {
                return [
                    'records' => $this->recordsFromCache($cached['items']),
                    'total' => $cached['total'],
                    'snapshot' => $snapshot,
                    'snapshot_version' => $durableSnapshot->version,
                    'cached' => true,
                ];
            } catch (\InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $matches = $snapshot->search($query);
        $total = count($matches);
        $records = array_slice($matches, ($page - 1) * $perPage, $perPage);
        $this->cache->put($key, [
            'items' => array_map(static fn (SicData $record): array => $record->toArray(), $records),
            'total' => $total,
        ], $this->ttl());

        return [
            'records' => $records,
            'total' => $total,
            'snapshot' => $snapshot,
            'snapshot_version' => $durableSnapshot->version,
            'cached' => false,
        ];
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
    public function changes(string $since, ?string $until = null): array
    {
        [, $current] = $this->currentSnapshots();
        $from = $this->snapshots->find(self::SNAPSHOT_DATASET, $since);
        $to = $until === null ? $current : $this->snapshots->find(self::SNAPSHOT_DATASET, $until);

        if ($from === null) {
            throw ApiException::invalidParameter('since', 'The requested SIC snapshot version was not found.');
        }

        if ($to === null) {
            throw ApiException::invalidParameter('until', 'The requested SIC snapshot version was not found.');
        }

        return [
            'since' => $from->version,
            'until' => $to->version,
            'from' => $from,
            'to' => $to,
            'diff' => $this->snapshots->diff(
                $from,
                $to,
                static fn (array $record): string => (string) ($record['code'] ?? ''),
            ),
        ];
    }

    /**
     * @return array{0: SicSnapshot, 1: DatasetSnapshot}
     */
    private function currentSnapshots(): array
    {
        $snapshot = $this->reference->snapshot();
        $payload = array_map(static fn (SicData $record): array => $record->toArray(), $snapshot->records);
        $durableSnapshot = $this->snapshots->captureIfChanged(
            self::SNAPSHOT_DATASET,
            self::SNAPSHOT_SCOPE,
            'companies_house',
            $payload,
            CarbonImmutable::parse($snapshot->retrievedAt),
        );

        return [$snapshot, $durableSnapshot];
    }

    private function ttl(): \DateTimeInterface
    {
        return now()->addHours(max(1, (int) config('ukapi.sic_reference.cache_ttl_hours')));
    }

    /**
     * @param  array<mixed>  $items
     * @return list<SicData>
     */
    private function recordsFromCache(array $items): array
    {
        $records = [];
        foreach ($items as $record) {
            if (! is_array($record)) {
                throw new \InvalidArgumentException('Cached SIC search response contains an invalid item.');
            }

            $records[] = SicData::fromArray($record);
        }

        return $records;
    }
}

<?php

namespace App\Domain\Companies;

use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;

final class SicLookup
{
    public function __construct(
        private readonly SicReference $reference,
        private readonly Repository $cache,
    ) {}

    /** @return array{sic: SicData, snapshot: SicSnapshot, cached: bool} */
    public function find(SicCode $code): array
    {
        $snapshot = $this->reference->snapshot();
        $key = sprintf('ukapi:v1:sic:%s:%s', $snapshot->checksum, $code->value());
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            try {
                return ['sic' => SicData::fromArray($cached), 'snapshot' => $snapshot, 'cached' => true];
            } catch (\InvalidArgumentException) {
                $this->cache->forget($key);
            }
        }

        $sic = $snapshot->find($code);
        if ($sic === null) {
            throw ApiException::sicNotFound();
        }

        $this->cache->put($key, $sic->toArray(), $this->ttl());

        return ['sic' => $sic, 'snapshot' => $snapshot, 'cached' => false];
    }

    /** @return array{records: list<SicData>, total: int, snapshot: SicSnapshot, cached: bool} */
    public function search(string $query, int $page, int $perPage): array
    {
        $snapshot = $this->reference->snapshot();
        $key = sprintf('ukapi:v1:sic:search:%s:%s:%d:%d', $snapshot->checksum, hash('sha256', mb_strtolower($query)), $page, $perPage);
        $cached = $this->cache->get($key);

        if (is_array($cached) && is_array($cached['items'] ?? null) && is_int($cached['total'] ?? null)) {
            try {
                return [
                    'records' => $this->recordsFromCache($cached['items']),
                    'total' => $cached['total'],
                    'snapshot' => $snapshot,
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

        return ['records' => $records, 'total' => $total, 'snapshot' => $snapshot, 'cached' => false];
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

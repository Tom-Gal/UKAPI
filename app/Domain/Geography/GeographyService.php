<?php

namespace App\Domain\Geography;

use App\Integrations\Contracts\PostcodeProvider;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;

final class GeographyService
{
    public function __construct(
        private readonly PostcodeProvider $provider,
        private readonly Repository $cache,
    ) {}

    public function lookup(UkPostcode $postcode): GeographyLookup
    {
        $entry = $this->postcodeEntry($postcode);

        if ($entry['state'] !== 'found') {
            throw ApiException::postcodeNotFound();
        }

        return new GeographyLookup(PostcodeData::fromArray($entry['data']), $entry['cached']);
    }

    public function validate(UkPostcode $postcode): GeographyValidation
    {
        $entry = $this->postcodeEntry($postcode);

        return new GeographyValidation($entry['state'] === 'found', $entry['cached']);
    }

    public function nearby(UkPostcode $postcode, int $limit, int $radiusMetres): GeographyCollection
    {
        $key = sprintf('ukapi:v1:geography:nearby:%s:%d:%d', $postcode->compact(), $limit, $radiusMetres);
        $cached = $this->cache->get($key);

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            return new GeographyCollection($this->postcodeList($cached['data']), true);
        }

        try {
            $records = $this->provider->nearby($postcode, $limit, $radiusMetres);
        } catch (ApiException $exception) {
            if ($exception->apiCode === 'postcode_not_found') {
                $this->cache->put($key, ['state' => 'not_found'], $this->negativeTtl());
            }

            throw $exception;
        }

        $data = array_map(static fn (PostcodeData $record): array => $record->toArray(), $records);
        $this->cache->put($key, ['state' => 'found', 'data' => $data], $this->positiveTtl());

        return new GeographyCollection($records, false);
    }

    public function reverse(Coordinates $coordinates): GeographyLookup
    {
        $key = 'ukapi:v1:geography:reverse:'.$coordinates->cacheKey();
        $cached = $this->cache->get($key);

        if (is_array($cached) && ($cached['state'] ?? null) === 'not_found') {
            throw ApiException::coordinatesNotFound();
        }

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            return new GeographyLookup(PostcodeData::fromArray($cached['data']), true);
        }

        $records = $this->provider->reverse($coordinates);

        if ($records === []) {
            $this->cache->put($key, ['state' => 'not_found'], $this->negativeTtl());

            throw ApiException::coordinatesNotFound();
        }

        $record = $records[0];
        $this->cache->put($key, ['state' => 'found', 'data' => $record->toArray()], $this->positiveTtl());

        return new GeographyLookup($record, false);
    }

    /**
     * @return array{state: 'found', cached: bool, data: array<string, mixed>}|array{state: 'not_found', cached: bool}
     */
    private function postcodeEntry(UkPostcode $postcode): array
    {
        $key = 'ukapi:v1:geography:postcode:'.$postcode->compact();
        $cached = $this->cache->get($key);

        if (is_array($cached)) {
            if (($cached['state'] ?? null) === 'not_found') {
                return ['state' => 'not_found', 'cached' => true];
            }

            if (($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
                return ['state' => 'found', 'data' => $cached['data'], 'cached' => true];
            }

            if (($cached['state'] ?? null) === 'found') {
                $this->cache->forget($key);
            }
        }

        try {
            $record = $this->provider->find($postcode);
        } catch (ApiException $exception) {
            if ($exception->apiCode === 'postcode_not_found') {
                $this->cache->put($key, ['state' => 'not_found'], $this->negativeTtl());

                return ['state' => 'not_found', 'cached' => false];
            }

            throw $exception;
        }

        $data = $record->toArray();
        $this->cache->put($key, ['state' => 'found', 'data' => $data], $this->positiveTtl());

        return ['state' => 'found', 'data' => $data, 'cached' => false];
    }

    /** @param array<int, mixed> $data
     * @return list<PostcodeData>
     */
    private function postcodeList(array $data): array
    {
        $records = [];

        foreach ($data as $record) {
            if (! is_array($record)) {
                continue;
            }

            $records[] = PostcodeData::fromArray($record);
        }

        return $records;
    }

    private function positiveTtl(): \DateTimeInterface
    {
        return now()->addDays(max(1, (int) config('ukapi.postcodes_io.cache_ttl_days')));
    }

    private function negativeTtl(): \DateTimeInterface
    {
        return now()->addMinutes(max(1, (int) config('ukapi.postcodes_io.negative_cache_ttl_minutes')));
    }
}

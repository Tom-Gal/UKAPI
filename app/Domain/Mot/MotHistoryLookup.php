<?php

namespace App\Domain\Mot;

use App\Integrations\Contracts\MotHistoryProvider;
use App\Support\Api\ApiException;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;

final class MotHistoryLookup
{
    public function __construct(
        private readonly MotHistoryProvider $provider,
        private readonly Repository $cache,
    ) {}

    public function byRegistration(string $registration): MotHistoryResult
    {
        return $this->find(
            'ukapi:v1:mot:registration:'.$registration,
            fn (): MotVehicleData => $this->provider->byRegistration($registration),
        );
    }

    public function byVin(string $vin): MotHistoryResult
    {
        return $this->find(
            'ukapi:v1:mot:vin:'.$vin,
            fn (): MotVehicleData => $this->provider->byVin($vin),
        );
    }

    /** @param callable(): MotVehicleData $fetch */
    private function find(string $key, callable $fetch): MotHistoryResult
    {
        $cached = $this->cache->get($key);
        if (is_array($cached) && ($cached['state'] ?? null) === 'not_found') {
            throw ApiException::motVehicleNotFound();
        }

        if (is_array($cached) && ($cached['state'] ?? null) === 'found' && is_array($cached['data'] ?? null)) {
            try {
                return new MotHistoryResult(MotVehicleData::fromArray($cached['data']), true);
            } catch (InvalidArgumentException) {
                $this->cache->forget($key);

                throw ApiException::providerInvalidResponse();
            }
        }

        try {
            $vehicle = $fetch();
        } catch (ApiException $exception) {
            if ($exception->apiCode === 'mot_vehicle_not_found') {
                $this->cache->put(
                    $key,
                    ['state' => 'not_found'],
                    now()->addMinutes(max(1, (int) config('ukapi.mot_history.negative_cache_ttl_minutes'))),
                );
            }

            throw $exception;
        }

        $this->cache->put(
            $key,
            ['state' => 'found', 'data' => $vehicle->toArray()],
            now()->addMinutes(max(1, (int) config('ukapi.mot_history.cache_ttl_minutes'))),
        );

        return new MotHistoryResult($vehicle, false);
    }
}

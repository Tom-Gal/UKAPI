<?php

namespace App\Integrations\EnvironmentAgency;

use App\Domain\Flood\FloodReading;
use App\Domain\Flood\FloodStation;
use App\Domain\Flood\FloodStationReference;
use App\Domain\Flood\FloodWarning;
use App\Domain\Geography\Coordinates;
use App\Integrations\Contracts\FloodProvider;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class EnvironmentAgencyFloodProvider implements FloodProvider
{
    public function warnings(?Coordinates $coordinates = null): array
    {
        $query = [];

        if ($coordinates !== null) {
            $query = [
                'lat' => $coordinates->latitude,
                'long' => $coordinates->longitude,
                'dist' => $this->nearbyDistanceKilometres(),
            ];
        }

        return $this->mapWarnings($this->get('id/floods', $query));
    }

    public function stationsNear(Coordinates $coordinates): array
    {
        return $this->mapStations($this->get('id/stations', [
            'lat' => $coordinates->latitude,
            'long' => $coordinates->longitude,
            'dist' => $this->nearbyDistanceKilometres(),
        ]));
    }

    public function readings(FloodStationReference $station): array
    {
        return $this->mapReadings($this->get(
            'id/stations/'.rawurlencode($station->value()).'/readings',
            ['_limit' => $this->maxReadings()],
        ));
    }

    /** @param array<string, float|int> $query */
    private function get(string $path, array $query): Response
    {
        $this->reserveProviderCapacity();

        try {
            return $this->request()->get($path, $query);
        } catch (ConnectionException) {
            throw ApiException::providerUnavailable();
        }
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('ukapi.environment_agency.base_url'))
            ->acceptJson()
            ->withUserAgent((string) config('ukapi.environment_agency.user_agent'))
            ->connectTimeout((int) config('ukapi.environment_agency.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.environment_agency.timeout_seconds'))
            ->retry(1, 150, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function reserveProviderCapacity(): void
    {
        $window = max(1, (int) config('ukapi.environment_agency.provider_limit_window_seconds'));
        $limit = max(1, (int) config('ukapi.environment_agency.provider_request_limit'));
        $key = 'ukapi:provider:environment_agency_flood:'.intdiv(now()->getTimestamp(), $window);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    /** @return list<FloodWarning> */
    private function mapWarnings(Response $response): array
    {
        $warnings = [];

        try {
            foreach ($this->items($response) as $item) {
                $warnings[] = FloodWarning::fromEnvironmentAgency($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $this->cap($warnings);
    }

    /** @return list<FloodStation> */
    private function mapStations(Response $response): array
    {
        $stations = [];

        try {
            foreach ($this->items($response) as $item) {
                $stations[] = FloodStation::fromEnvironmentAgency($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $this->cap($stations);
    }

    /** @return list<FloodReading> */
    private function mapReadings(Response $response): array
    {
        $readings = [];

        try {
            foreach ($this->items($response, true) as $item) {
                $readings[] = FloodReading::fromEnvironmentAgency($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $this->cap($readings);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function items(Response $response, bool $notFoundIsStation = false): array
    {
        if ($response->status() === 404 && $notFoundIsStation) {
            throw ApiException::floodStationNotFound();
        }

        if (in_array($response->status(), [401, 403, 429], true) || $response->serverError()) {
            throw ApiException::providerUnavailable();
        }

        if (! $response->successful()) {
            throw ApiException::providerInvalidResponse();
        }

        $payload = $response->json();
        $items = is_array($payload) ? ($payload['items'] ?? null) : null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw ApiException::providerInvalidResponse();
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw ApiException::providerInvalidResponse();
            }
        }

        /** @var list<array<string, mixed>> $items */
        return $items;
    }

    /** @template T
     * @param  list<T>  $records
     * @return list<T>
     */
    private function cap(array $records): array
    {
        return array_slice($records, 0, max(1, (int) config('ukapi.environment_agency.max_results')));
    }

    private function nearbyDistanceKilometres(): int
    {
        return max(1, min(100, (int) config('ukapi.environment_agency.nearby_distance_kilometres')));
    }

    private function maxReadings(): int
    {
        return max(1, min(100, (int) config('ukapi.environment_agency.max_readings')));
    }
}

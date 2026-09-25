<?php

namespace App\Integrations\PoliceUk;

use App\Domain\Crime\CrimeCategory;
use App\Domain\Crime\CrimeData;
use App\Domain\Crime\YearMonth;
use App\Domain\Geography\Coordinates;
use App\Integrations\Contracts\CrimeProvider;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class PoliceUkCrimeProvider implements CrimeProvider
{
    public function nearby(Coordinates $coordinates, ?YearMonth $month): array
    {
        $query = [
            'lat' => $coordinates->latitude,
            'lng' => $coordinates->longitude,
        ];

        if ($month !== null) {
            $query['date'] = $month->value();
        }

        return $this->mapCrimes($this->get('crimes-street/all-crime', $query));
    }

    public function categories(?YearMonth $month): array
    {
        $query = $month === null ? [] : ['date' => $month->value()];

        return $this->mapCategories($this->get('crime-categories', $query));
    }

    /** @param array<string, float|string> $query */
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
        $request = Http::baseUrl((string) config('ukapi.police_uk.base_url'))
            ->acceptJson()
            ->withUserAgent((string) config('ukapi.police_uk.user_agent'))
            ->connectTimeout((int) config('ukapi.police_uk.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.police_uk.timeout_seconds'))
            ->retry(1, 150, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function reserveProviderCapacity(): void
    {
        $window = max(1, (int) config('ukapi.police_uk.provider_limit_window_seconds'));
        $limit = max(1, (int) config('ukapi.police_uk.provider_request_limit'));
        $key = 'ukapi:provider:police_uk:'.intdiv(now()->getTimestamp(), $window);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    /** @return list<CrimeData> */
    private function mapCrimes(Response $response): array
    {
        $payload = $this->payload($response);
        $crimes = [];

        try {
            foreach ($payload as $item) {
                if (! is_array($item)) {
                    throw new InvalidArgumentException('Police.uk crime entry is not an object.');
                }

                $crimes[] = CrimeData::fromPoliceUk($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $crimes;
    }

    /** @return list<CrimeCategory> */
    private function mapCategories(Response $response): array
    {
        $payload = $this->payload($response);
        $categories = [];

        try {
            foreach ($payload as $item) {
                if (! is_array($item)) {
                    throw new InvalidArgumentException('Police.uk category entry is not an object.');
                }

                $categories[] = CrimeCategory::fromPoliceUk($item);
            }
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }

        return $categories;
    }

    /** @return list<mixed> */
    private function payload(Response $response): array
    {
        if ($response->status() === 429 || $response->serverError()) {
            throw ApiException::providerUnavailable();
        }

        if (! $response->successful()) {
            throw ApiException::providerInvalidResponse();
        }

        $payload = $response->json();

        if (! is_array($payload) || ! array_is_list($payload)) {
            throw ApiException::providerInvalidResponse();
        }

        return $payload;
    }
}

<?php

namespace App\Integrations\Planning;

use App\Domain\Planning\PlanningConstraintCollection;
use App\Domain\Planning\PlanningConstraintData;
use App\Domain\Planning\PlanningConstraintQuery;
use App\Integrations\Contracts\PlanningDataProvider as PlanningDataProviderContract;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class PlanningDataProvider implements PlanningDataProviderContract
{
    public function constraints(PlanningConstraintQuery $query): PlanningConstraintCollection
    {
        $parameters = [
            'latitude' => $query->latitude,
            'longitude' => $query->longitude,
            'limit' => $query->limit,
        ];
        $queryString = collect($parameters)
            ->map(fn (int|float $value, string $key): string => rawurlencode($key).'='.rawurlencode((string) $value))
            ->concat(array_map(
                static fn (string $dataset): string => 'dataset='.rawurlencode($dataset),
                PlanningConstraintData::DATASETS,
            ))
            ->implode('&');

        $payload = $this->payload($this->get('entity.json?'.$queryString));
        $items = $payload['entities'] ?? null;

        if (! is_array($items) || ! array_is_list($items)) {
            throw ApiException::providerInvalidResponse();
        }

        try {
            $constraints = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    throw new InvalidArgumentException('Planning entity item is invalid.');
                }

                $constraints[] = PlanningConstraintData::fromPlanningData($item);
            }

            return new PlanningConstraintCollection($constraints);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    private function get(string $path): Response
    {
        $this->reserveProviderCapacity();

        try {
            return $this->request()->get($path);
        } catch (ConnectionException) {
            throw ApiException::providerUnavailable();
        }
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl((string) config('ukapi.planning_data.base_url'))
            ->acceptJson()
            ->withUserAgent((string) config('ukapi.planning_data.user_agent'))
            ->connectTimeout((int) config('ukapi.planning_data.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.planning_data.timeout_seconds'))
            ->retry(1, 100, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function reserveProviderCapacity(): void
    {
        $window = max(1, (int) config('ukapi.planning_data.provider_limit_window_seconds'));
        $limit = max(1, (int) config('ukapi.planning_data.provider_request_limit'));
        $key = 'ukapi:provider:planning_data:'.intdiv(now()->getTimestamp(), $window);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    /** @return array<string, mixed> */
    private function payload(Response $response): array
    {
        if ($response->status() === 429 || $response->status() === 403 || $response->serverError()) {
            throw ApiException::providerUnavailable();
        }

        if (! $response->successful()) {
            throw ApiException::providerInvalidResponse();
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw ApiException::providerInvalidResponse();
        }

        return $payload;
    }
}

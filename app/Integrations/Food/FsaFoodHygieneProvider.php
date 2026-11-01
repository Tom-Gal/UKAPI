<?php

namespace App\Integrations\Food;

use App\Domain\Food\FoodEstablishmentCollection;
use App\Domain\Food\FoodEstablishmentData;
use App\Domain\Food\FoodEstablishmentId;
use App\Domain\Food\FoodEstablishmentSearch;
use App\Integrations\Contracts\FoodHygieneProvider;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class FsaFoodHygieneProvider implements FoodHygieneProvider
{
    public function find(FoodEstablishmentId $id): FoodEstablishmentData
    {
        $response = $this->get('Establishments/'.rawurlencode((string) $id->value()));

        if ($response->status() === 404) {
            throw ApiException::foodEstablishmentNotFound();
        }

        $payload = $this->payload($response);

        try {
            return FoodEstablishmentData::fromFsa($payload);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    public function search(FoodEstablishmentSearch $search): FoodEstablishmentCollection
    {
        $query = [
            'pageNumber' => $search->page,
            'pageSize' => $search->perPage,
            'sortOptionKey' => $search->latitude !== null ? 'distance' : 'relevance',
        ];

        if ($search->name !== null) {
            $query['name'] = $search->name;
        }
        if ($search->postcode !== null) {
            $query['address'] = $search->postcode->display();
        }
        if ($search->latitude !== null && $search->longitude !== null && $search->radiusMiles !== null) {
            $query['latitude'] = $search->latitude;
            $query['longitude'] = $search->longitude;
            $query['maxDistanceLimit'] = round($search->radiusMiles, 3);
        }
        if ($search->schemeType !== null) {
            $query['schemeTypeKey'] = $search->schemeType;
        }
        if ($search->rating !== null) {
            $query['ratingKey'] = $search->rating;
        }
        if ($search->ratingOperator !== null) {
            $query['ratingOperatorKey'] = $search->ratingOperator;
        }

        $payload = $this->payload($this->get('Establishments', $query));
        $items = $payload['establishments'] ?? null;
        $meta = $payload['meta'] ?? null;

        if (! is_array($items) || ! array_is_list($items) || ! is_array($meta)) {
            throw ApiException::providerInvalidResponse();
        }

        try {
            $establishments = [];
            foreach ($items as $item) {
                if (! is_array($item)) {
                    throw new InvalidArgumentException('FSA establishment item is invalid.');
                }

                $establishments[] = FoodEstablishmentData::fromFsa($item);
            }

            $total = $meta['totalCount'] ?? count($establishments);
            if (! is_int($total) && ! is_numeric($total)) {
                throw new InvalidArgumentException('FSA total count is invalid.');
            }

            return new FoodEstablishmentCollection(
                establishments: $establishments,
                total: (int) $total,
                sourceUpdatedAt: $this->sourceTimestamp($meta['extractDate'] ?? null),
            );
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    /** @param array<string, int|float|string> $query */
    private function get(string $path, array $query = []): Response
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
        $request = Http::baseUrl((string) config('ukapi.food_hygiene.base_url'))
            ->acceptJson()
            ->withHeaders(['x-api-version' => '2'])
            ->withUserAgent((string) config('ukapi.food_hygiene.user_agent'))
            ->connectTimeout((int) config('ukapi.food_hygiene.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.food_hygiene.timeout_seconds'))
            ->retry(1, 100, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function reserveProviderCapacity(): void
    {
        $window = max(1, (int) config('ukapi.food_hygiene.provider_limit_window_seconds'));
        $limit = max(1, (int) config('ukapi.food_hygiene.provider_request_limit'));
        $key = 'ukapi:provider:food_hygiene:'.intdiv(now()->getTimestamp(), $window);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    /** @return array<string, mixed> */
    private function payload(Response $response): array
    {
        if ($response->status() === 401) {
            throw ApiException::providerAuthenticationFailed();
        }

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

    private function sourceTimestamp(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }
}

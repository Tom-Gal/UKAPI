<?php

namespace App\Integrations\CompaniesHouse;

use App\Domain\Companies\CompanyData;
use App\Domain\Companies\CompanyFilings;
use App\Domain\Companies\CompanyNumber;
use App\Domain\Companies\CompanyOfficers;
use App\Domain\Companies\CompanySearchResults;
use App\Integrations\Contracts\CompanyProvider;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

final class CompaniesHouseCompanyProvider implements CompanyProvider
{
    public function find(CompanyNumber $companyNumber): CompanyData
    {
        $payload = $this->payload($this->get('company/'.rawurlencode($companyNumber->value())), true);
        // The profile resource identifies the company through the request path
        // rather than repeating its number in every response body.
        $payload['company_number'] ??= $companyNumber->value();

        try {
            return CompanyData::fromCompaniesHouse($payload);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    public function search(string $query, int $page, int $perPage): CompanySearchResults
    {
        $payload = $this->payload($this->get('search/companies', [
            'q' => $query,
            'items_per_page' => $perPage,
            'start_index' => ($page - 1) * $perPage,
        ]));

        try {
            return CompanySearchResults::fromCompaniesHouse($payload);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    public function officers(CompanyNumber $companyNumber, int $page, int $perPage): CompanyOfficers
    {
        $payload = $this->payload($this->get(
            'company/'.rawurlencode($companyNumber->value()).'/officers',
            [
                'items_per_page' => $perPage,
                'start_index' => ($page - 1) * $perPage,
            ],
        ), true);

        try {
            return CompanyOfficers::fromCompaniesHouse($payload);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    public function filings(CompanyNumber $companyNumber, int $page, int $perPage): CompanyFilings
    {
        $payload = $this->payload($this->get(
            'company/'.rawurlencode($companyNumber->value()).'/filing-history',
            [
                'items_per_page' => $perPage,
                'start_index' => ($page - 1) * $perPage,
            ],
        ), true);

        try {
            return CompanyFilings::fromCompaniesHouse($payload);
        } catch (InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    /** @param array<string, int|string> $query */
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
        $apiKey = config('ukapi.companies_house.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw ApiException::providerNotConfigured();
        }

        $request = Http::baseUrl((string) config('ukapi.companies_house.base_url'))
            ->acceptJson()
            ->withBasicAuth($apiKey, '')
            ->withUserAgent((string) config('ukapi.companies_house.user_agent'))
            ->connectTimeout((int) config('ukapi.companies_house.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.companies_house.timeout_seconds'))
            ->retry(2, 150, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function reserveProviderCapacity(): void
    {
        $key = 'ukapi:provider:companies_house:'.intdiv(now()->getTimestamp(), (int) config('ukapi.companies_house.provider_limit_window_seconds'));
        $limit = (int) config('ukapi.companies_house.provider_request_limit');
        $window = (int) config('ukapi.companies_house.provider_limit_window_seconds');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    /** @return array<string, mixed> */
    private function payload(Response $response, bool $notFoundIsCompany = false): array
    {
        if ($response->status() === 404 && $notFoundIsCompany) {
            throw ApiException::companyNotFound();
        }

        if (in_array($response->status(), [401, 403, 429], true) || $response->serverError()) {
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

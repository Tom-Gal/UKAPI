<?php

namespace App\Domain\Health;

use App\Domain\Companies\CompanySearchResults;
use App\Domain\Companies\SicReference;
use App\Domain\Crime\CrimeCategory;
use App\Domain\Flood\FloodWarning;
use App\Domain\Geography\PostcodeData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

final class HealthProbe
{
    public function __construct(private readonly Repository $cache, private readonly SicReference $sic) {}

    public function check(string $service): ProbeResult
    {
        return match ($service) {
            'application' => $this->application(),
            'postcodes' => $this->postcodes(),
            'crime' => $this->crime(),
            'flood' => $this->flood(),
            'companies' => $this->companies(),
            'sic' => new ProbeResult(referenceRetrievedAt: CarbonImmutable::parse($this->sic->snapshot()->retrievedAt)),
            default => throw new RuntimeException('Unknown health service.'),
        };
    }

    private function application(): ProbeResult
    {
        // Match /ready: this checks dependencies, not external HTTP or login flows.
        DB::select('select 1');
        $this->cache->get('ukapi:health:readiness');

        return new ProbeResult;
    }

    private function postcodes(): ProbeResult
    {
        $payload = $this->get('postcodes_io', 'postcodes/SW1A1AA')->json();
        $result = is_array($payload) ? ($payload['result'] ?? null) : null;
        if (! is_array($result)) {
            throw new RuntimeException('Invalid postcode probe.');
        }
        PostcodeData::fromPostcodesIo($result);

        return new ProbeResult;
    }

    private function crime(): ProbeResult
    {
        $items = $this->get('police_uk', 'crime-categories')->json();
        if (! is_array($items) || ! array_is_list($items) || $items === []) {
            throw new RuntimeException('Invalid crime probe.');
        }
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Invalid crime category.');
            }
            CrimeCategory::fromPoliceUk($item);
        }

        // Metadata failure does not turn a working categories endpoint into an outage.
        // Nor does a successful HTTP response prove that the dataset is current.
        try {
            $date = $this->get('police_uk', 'crime-last-updated')->json('date');
            if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) {
                $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);
                if ($parsed !== null && $parsed->format('Y-m-d') === $date && ! $parsed->isFuture()) {
                    return new ProbeResult(sourceDate: $parsed->startOfMonth());
                }
            }
        } catch (\Throwable) {
            // Never retain provider bodies, exception messages, URLs or credentials.
        }

        return new ProbeResult;
    }

    private function flood(): ProbeResult
    {
        $items = $this->get('environment_agency', 'id/floods', ['_limit' => 1])->json('items');
        if (! is_array($items) || ! array_is_list($items)) {
            throw new RuntimeException('Invalid flood probe.');
        }
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException('Invalid flood warning.');
            }
            FloodWarning::fromEnvironmentAgency($item);
        }

        // An empty warnings list is valid. Warning issue times are not dataset freshness.
        return new ProbeResult;
    }

    private function companies(): ProbeResult
    {
        $payload = $this->get('companies_house', 'search/companies', [
            'q' => 'company', 'items_per_page' => 1, 'start_index' => 0,
        ])->json();
        if (! is_array($payload) || ! is_array($payload['items'] ?? null)
            || ! array_is_list($payload['items']) || ! isset($payload['total_results'])) {
            throw new RuntimeException('Invalid company probe.');
        }
        CompanySearchResults::fromCompaniesHouse($payload);

        return new ProbeResult;
    }

    /** @param array<string, int|string> $query */
    private function get(string $provider, string $path, array $query = []): Response
    {
        $apiKey = config('ukapi.companies_house.api_key');
        if ($provider === 'companies_house' && (! is_string($apiKey) || trim($apiKey) === '')) {
            throw new RuntimeException('Company probe unavailable.');
        }

        // Share the integrations' provider budget, but bypass customer caches and retries.
        if ($provider !== 'postcodes_io') {
            $window = max(1, (int) config("ukapi.{$provider}.provider_limit_window_seconds"));
            $limit = max(1, (int) config("ukapi.{$provider}.provider_request_limit"));
            $budget = $provider === 'environment_agency' ? 'environment_agency_flood' : $provider;
            $key = 'ukapi:provider:'.$budget.':'.intdiv(now()->getTimestamp(), $window);
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw new RuntimeException('Probe capacity unavailable.');
            }
            RateLimiter::hit($key, $window);
        }

        $timeout = max(1, min(10, (int) config('health.timeout_seconds')));
        $maxBytes = max(1024, min(1_048_576, (int) config('health.max_response_bytes')));
        $request = Http::baseUrl((string) config("ukapi.{$provider}.base_url"))
            ->acceptJson()
            ->withUserAgent((string) config("ukapi.{$provider}.user_agent", 'UKApi.co.uk/1.0'))
            ->connectTimeout(max(1, min($timeout, (int) config('health.connect_timeout_seconds'))))
            ->timeout($timeout)
            ->withoutRedirecting()
            ->withOptions(['progress' => static function ($total, $downloaded) use ($maxBytes): void {
                if ($total > $maxBytes || $downloaded > $maxBytes) {
                    throw new RuntimeException('Probe response exceeded size limit.');
                }
            }]);

        $caBundle = config('ukapi.http_ca_bundle');
        if ($caBundle !== null && $caBundle !== '') {
            $request = $request->withOptions(['verify' => $caBundle]);
        }
        if ($provider === 'companies_house') {
            $request = $request->withBasicAuth(trim($apiKey), '');
        }

        $response = $request->get($path, $query);
        if (! $response->successful() || strlen($response->body()) > $maxBytes) {
            throw new RuntimeException('Provider probe failed.');
        }

        return $response;
    }
}

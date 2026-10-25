<?php

namespace App\Integrations\MotHistory;

use App\Integrations\Contracts\MotHistoryProvider as MotHistoryProviderContract;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

final class MotHistoryProvider implements MotHistoryProviderContract
{
    public function byRegistration(string $registration): array
    {
        return $this->get('v1/trade/vehicles/registration/'.rawurlencode($registration));
    }

    public function byVin(string $vin): array
    {
        return $this->get('v1/trade/vehicles/vin/'.rawurlencode($vin));
    }

    /** @return array<string, mixed> */
    private function get(string $path): array
    {
        $this->reserveProviderCapacity();

        try {
            $response = $this->request()->get($path);
        } catch (ConnectionException) {
            throw ApiException::providerUnavailable();
        }

        if ($response->status() === 404) {
            throw ApiException::motVehicleNotFound();
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw ApiException::providerAuthenticationFailed();
        }

        if ($response->status() === 429 || $response->serverError()) {
            throw ApiException::providerUnavailable();
        }

        if (! $response->successful()) {
            throw ApiException::providerInvalidResponse();
        }

        $payload = $response->json();

        if (! is_array($payload) || array_is_list($payload)) {
            throw ApiException::providerInvalidResponse();
        }

        return $payload;
    }

    private function reserveProviderCapacity(): void
    {
        $window = max(1, (int) config('ukapi.mot_history.provider_limit_window_seconds'));
        $limit = max(1, (int) config('ukapi.mot_history.provider_request_limit'));
        $key = 'ukapi:provider:mot_history:'.intdiv(now()->getTimestamp(), $window);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ApiException::providerUnavailable();
        }

        RateLimiter::hit($key, $window);
    }

    private function request(): PendingRequest
    {
        $apiKey = trim((string) config('ukapi.mot_history.api_key'));
        $accessToken = $this->accessToken();

        if ($apiKey === '') {
            throw ApiException::providerNotConfigured();
        }

        $request = Http::baseUrl((string) config('ukapi.mot_history.base_url'))
            ->acceptJson()
            ->withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'X-API-Key' => $apiKey,
            ])
            ->connectTimeout((int) config('ukapi.mot_history.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.mot_history.timeout_seconds'))
            ->retry(1, 150, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        return is_string($caBundle) && $caBundle !== ''
            ? $request->withOptions(['verify' => $caBundle])
            : $request;
    }

    private function accessToken(): string
    {
        $configuredToken = trim((string) config('ukapi.mot_history.access_token'));

        if ($configuredToken !== '') {
            return $configuredToken;
        }

        $clientId = trim((string) config('ukapi.mot_history.client_id'));
        $clientSecret = trim((string) config('ukapi.mot_history.client_secret'));
        $tokenUrl = trim((string) config('ukapi.mot_history.token_url'));
        $scope = trim((string) config('ukapi.mot_history.scope'));

        if ($clientId === '' || $clientSecret === '' || $tokenUrl === '' || $scope === '') {
            throw ApiException::providerNotConfigured();
        }

        $cacheKey = 'ukapi:provider:mot_history:access_token:'.hash('sha256', $clientId);
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout((int) config('ukapi.mot_history.connect_timeout_seconds'))
                ->timeout((int) config('ukapi.mot_history.timeout_seconds'))
                ->post($tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => $scope,
                ]);
        } catch (ConnectionException) {
            throw ApiException::providerUnavailable();
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw ApiException::providerAuthenticationFailed();
        }

        if ($response->status() === 429 || $response->serverError()) {
            throw ApiException::providerUnavailable();
        }

        $token = $response->json('access_token');
        $expiresIn = (int) $response->json('expires_in', 3600);

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw ApiException::providerInvalidResponse();
        }

        Cache::put($cacheKey, $token, now()->addSeconds(max(60, $expiresIn - 60)));

        return $token;
    }
}

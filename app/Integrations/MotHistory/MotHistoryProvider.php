<?php

namespace App\Integrations\MotHistory;

use App\Integrations\Contracts\MotHistoryProvider as MotHistoryProviderContract;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

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

    private function request(): PendingRequest
    {
        $apiKey = trim((string) config('ukapi.mot_history.api_key'));
        $accessToken = trim((string) config('ukapi.mot_history.access_token'));

        if ($apiKey === '' || $accessToken === '') {
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
}

<?php

namespace App\Integrations\GovUk;

use App\Domain\Calendar\BankHolidayCalendar;
use App\Domain\Calendar\BankHolidayDivision;
use App\Integrations\Contracts\BankHolidayProvider;
use App\Support\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class GovUkBankHolidayProvider implements BankHolidayProvider
{
    public function calendar(): BankHolidayCalendar
    {
        try {
            $response = $this->request()->get((string) config('ukapi.govuk_bank_holidays.url'));
        } catch (ConnectionException) {
            throw ApiException::providerUnavailable();
        }

        if (! $response->successful()) {
            throw ApiException::providerUnavailable();
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw ApiException::providerInvalidResponse();
        }

        $divisions = [];
        foreach (['england-and-wales', 'scotland', 'northern-ireland'] as $division) {
            if (! is_array($payload[$division] ?? null)) {
                throw ApiException::providerInvalidResponse();
            }

            $source = $payload[$division];
            $events = $source['events'] ?? null;
            if (! is_array($events)) {
                throw ApiException::providerInvalidResponse();
            }

            try {
                $divisions[$division] = BankHolidayDivision::fromArray([
                    'division' => $division,
                    'events' => $events,
                ]);
            } catch (\InvalidArgumentException) {
                throw ApiException::providerInvalidResponse();
            }
        }

        try {
            return BankHolidayCalendar::fromArray(['divisions' => array_map(
                static fn (BankHolidayDivision $item): array => $item->toArray(),
                $divisions,
            )]);
        } catch (\InvalidArgumentException) {
            throw ApiException::providerInvalidResponse();
        }
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->connectTimeout((int) config('ukapi.govuk_bank_holidays.connect_timeout_seconds'))
            ->timeout((int) config('ukapi.govuk_bank_holidays.timeout_seconds'))
            ->retry(1, 100, throw: false);

        $caBundle = config('ukapi.http_ca_bundle');

        if (is_string($caBundle) && $caBundle !== '') {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        return $request;
    }
}

<?php

namespace App\Integrations\Contracts;

interface MotHistoryProvider
{
    /** @return array<string, mixed> */
    public function byRegistration(string $registration): array;

    /** @return array<string, mixed> */
    public function byVin(string $vin): array;
}

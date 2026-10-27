<?php

namespace App\Integrations\Contracts;

use App\Domain\Mot\MotVehicleData;

interface MotHistoryProvider
{
    public function byRegistration(string $registration): MotVehicleData;

    public function byVin(string $vin): MotVehicleData;
}

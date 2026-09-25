<?php

namespace App\Integrations\Contracts;

use App\Domain\Flood\FloodReading;
use App\Domain\Flood\FloodStation;
use App\Domain\Flood\FloodStationReference;
use App\Domain\Flood\FloodWarning;
use App\Domain\Geography\Coordinates;

interface FloodProvider
{
    /** @return list<FloodWarning> */
    public function warnings(?Coordinates $coordinates = null): array;

    /** @return list<FloodStation> */
    public function stationsNear(Coordinates $coordinates): array;

    /** @return list<FloodReading> */
    public function readings(FloodStationReference $station): array;
}

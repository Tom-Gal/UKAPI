<?php

namespace App\Domain\Mot;

final readonly class MotHistoryResult
{
    public function __construct(
        public MotVehicleData $vehicle,
        public bool $cached,
    ) {}
}

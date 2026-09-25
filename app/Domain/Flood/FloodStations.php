<?php

namespace App\Domain\Flood;

final readonly class FloodStations
{
    /** @param list<FloodStation> $stations */
    public function __construct(
        public array $stations,
        public bool $cached,
        public string $retrievedAt,
    ) {}
}

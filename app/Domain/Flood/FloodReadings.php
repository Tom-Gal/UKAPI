<?php

namespace App\Domain\Flood;

final readonly class FloodReadings
{
    /** @param list<FloodReading> $readings */
    public function __construct(
        public array $readings,
        public bool $cached,
        public string $retrievedAt,
        public ?string $sourceUpdatedAt,
        public ?string $snapshotVersion = null,
        public ?string $snapshotChecksum = null,
        public ?string $snapshotCapturedAt = null,
    ) {}
}

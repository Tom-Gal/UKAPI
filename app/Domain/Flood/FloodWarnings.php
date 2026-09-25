<?php

namespace App\Domain\Flood;

final readonly class FloodWarnings
{
    /** @param list<FloodWarning> $warnings */
    public function __construct(
        public array $warnings,
        public bool $cached,
        public string $retrievedAt,
        public ?string $sourceUpdatedAt,
    ) {}
}

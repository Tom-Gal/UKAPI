<?php

namespace App\Domain\Health;

use Carbon\CarbonImmutable;

final readonly class ProbeResult
{
    public function __construct(
        public ?CarbonImmutable $sourceDate = null,
        public ?CarbonImmutable $referenceRetrievedAt = null,
    ) {}
}

<?php

namespace App\Domain\Geography;

final readonly class OutcodeLookup
{
    public function __construct(
        public OutcodeData $outcode,
        public bool $cached,
    ) {}
}

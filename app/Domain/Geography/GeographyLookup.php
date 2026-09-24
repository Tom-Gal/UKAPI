<?php

namespace App\Domain\Geography;

final readonly class GeographyLookup
{
    public function __construct(
        public PostcodeData $postcode,
        public bool $cached,
    ) {}
}

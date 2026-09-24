<?php

namespace App\Domain\Geography;

final readonly class GeographyValidation
{
    public function __construct(
        public bool $valid,
        public bool $cached,
    ) {}
}

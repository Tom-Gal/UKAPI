<?php

namespace App\Domain\Food;

final readonly class FoodEstablishmentLookup
{
    public function __construct(
        public FoodEstablishmentData $establishment,
        public bool $cached,
        public bool $stale,
    ) {}
}

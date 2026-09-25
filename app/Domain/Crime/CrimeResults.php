<?php

namespace App\Domain\Crime;

final readonly class CrimeResults
{
    /** @param list<CrimeData> $crimes */
    public function __construct(
        public array $crimes,
        public bool $cached,
    ) {}
}

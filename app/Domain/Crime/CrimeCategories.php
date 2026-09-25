<?php

namespace App\Domain\Crime;

final readonly class CrimeCategories
{
    /** @param list<CrimeCategory> $categories */
    public function __construct(
        public array $categories,
        public bool $cached,
    ) {}
}

<?php

namespace App\Domain\Geography;

final readonly class GeographyCollection
{
    /** @param list<PostcodeData> $postcodes */
    public function __construct(
        public array $postcodes,
        public bool $cached,
    ) {}
}

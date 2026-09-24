<?php

namespace App\Domain\Companies;

final readonly class CompanyProfile
{
    public function __construct(
        public CompanyData $company,
        public bool $cached,
        public bool $stale,
    ) {}
}

<?php

namespace App\Domain\Companies;

use App\Models\DatasetSnapshot;

final readonly class CompanyProfileSnapshotResult
{
    public function __construct(
        public DatasetSnapshot $snapshot,
        public bool $changed,
    ) {}
}

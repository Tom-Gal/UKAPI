<?php

namespace App\Domain\Companies;

use App\Domain\Snapshots\SnapshotStore;
use App\Domain\Sources\SourceRegister;
use App\Integrations\Contracts\CompanyProvider;
use LogicException;

final class CompanyProfileSnapshotter
{
    public const DATASET = 'companies_house.company_profile';

    public function __construct(
        private readonly CompanyProvider $provider,
        private readonly SnapshotStore $snapshots,
        private readonly SourceRegister $sources,
    ) {}

    public function capture(CompanyNumber $companyNumber): CompanyProfileSnapshotResult
    {
        if (! $this->sources->isReviewed('companies_house')) {
            throw new LogicException('Companies House profile snapshots require an approved source review before capture.');
        }

        $active = $this->snapshots->active(self::DATASET, $companyNumber->value());
        $company = $this->provider->find($companyNumber);
        $snapshot = $this->snapshots->captureIfChanged(
            self::DATASET,
            $companyNumber->value(),
            'companies_house',
            [$company->toArray()],
        );

        return new CompanyProfileSnapshotResult(
            snapshot: $snapshot,
            changed: $active?->checksum !== $snapshot->checksum,
        );
    }
}

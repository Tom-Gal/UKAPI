<?php

namespace App\Console\Commands;

use App\Domain\Companies\CompanyNumber;
use App\Domain\Companies\CompanyProfileSnapshotter;
use App\Support\Api\ApiException;
use Illuminate\Console\Command;
use LogicException;

final class CaptureCompanyProfileSnapshot extends Command
{
    protected $signature = 'ukapi:companies:profile-snapshot
        {companyNumber : The Companies House company number to capture}';

    protected $description = 'Capture an approved, normalised Companies House company profile snapshot.';

    public function handle(CompanyProfileSnapshotter $snapshotter): int
    {
        try {
            $companyNumber = CompanyNumber::from((string) $this->argument('companyNumber'));
            $result = $snapshotter->capture($companyNumber);
        } catch (ApiException $exception) {
            $this->error(sprintf('Companies House profile snapshot failed (%s).', $exception->apiCode));

            return self::FAILURE;
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Companies House profile snapshot failed unexpectedly.');

            return self::FAILURE;
        }

        $message = $result->changed ? 'Captured' : 'No change; retained';
        $this->info(sprintf(
            '%s Companies House profile snapshot for %s as %s.',
            $message,
            $companyNumber->value(),
            $result->snapshot->version,
        ));

        return self::SUCCESS;
    }
}

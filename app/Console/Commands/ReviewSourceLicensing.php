<?php

namespace App\Console\Commands;

use App\Domain\Sources\SourceRegister;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class ReviewSourceLicensing extends Command
{
    protected $signature = 'ukapi:sources:review
        {--due : Show only source reviews that are due}
        {--as-of= : Evaluate due dates at this ISO-8601 date for an auditable check}';

    protected $description = 'Report source, licence and attribution reviews that need operator attention.';

    public function handle(SourceRegister $sources): int
    {
        try {
            $asOf = $this->option('as-of')
                ? CarbonImmutable::parse((string) $this->option('as-of'))
                : CarbonImmutable::now();
            $records = $this->option('due') ? $sources->due($asOf) : $sources->all();
        } catch (\Throwable) {
            $this->error('The source review date must be a valid ISO-8601 date.');

            return self::FAILURE;
        }

        $rows = array_map(fn (array $source): array => [
            (string) $source['label'],
            (string) $source['review_owner'],
            (string) ($source['last_reviewed_at'] ?? 'never'),
            $sources->dueAt($source)->toDateString(),
            $sources->reviewStatus($source, $asOf),
            (string) $source['review_status'],
        ], $records);

        $this->table(
            ['Source', 'Owner', 'Last reviewed', 'Due', 'Schedule', 'Review note'],
            $rows,
        );

        if ($records === []) {
            $this->info($this->option('due')
                ? 'No source reviews are due.'
                : 'No source review records are configured.');

            return self::SUCCESS;
        }

        if ($this->option('due')) {
            $this->error(sprintf('%d source review(s) require attention.', count($records)));

            return self::FAILURE;
        }

        $this->line('Review each source record for licence, attribution, coverage, stability, rate limits, schema changes and public-register accuracy.');

        return self::SUCCESS;
    }
}

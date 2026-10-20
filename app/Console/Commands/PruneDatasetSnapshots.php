<?php

namespace App\Console\Commands;

use App\Domain\Snapshots\SnapshotStore;
use Illuminate\Console\Command;

final class PruneDatasetSnapshots extends Command
{
    protected $signature = 'ukapi:snapshots:prune {--dataset= : Only prune one dataset}';

    protected $description = 'Remove retained dataset snapshots while preserving active versions.';

    public function handle(SnapshotStore $snapshots): int
    {
        $retentionDays = max(1, (int) config('ukapi.snapshot_retention_days', 365));
        $deleted = $snapshots->prune(
            now()->subDays($retentionDays),
            $this->option('dataset') ?: null,
        );

        $this->info("Pruned {$deleted} dataset snapshot(s).");

        return self::SUCCESS;
    }
}

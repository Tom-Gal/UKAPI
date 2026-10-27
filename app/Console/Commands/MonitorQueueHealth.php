<?php

namespace App\Console\Commands;

use App\Domain\Operations\QueueHealthMonitor;
use Illuminate\Console\Command;

final class MonitorQueueHealth extends Command
{
    protected $signature = 'ukapi:queue:monitor';

    protected $description = 'Monitor the database queue backlog and failed jobs.';

    public function handle(QueueHealthMonitor $monitor): int
    {
        if (! (bool) config('ukapi.operations.queue_monitor.enabled')) {
            $this->comment('Queue monitoring is disabled.');

            return self::SUCCESS;
        }

        if (config('queue.default') !== 'database') {
            $this->error('Queue monitoring expects the database queue connection.');

            return self::FAILURE;
        }

        try {
            $result = $monitor->check();
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Queue health could not be read.');

            return self::FAILURE;
        }

        $summary = sprintf(
            'Queue %s: pending=%d failed=%d oldest=%s alerted=%s',
            $result['status'],
            $result['pending'],
            $result['failed'],
            $result['oldest_age_seconds'] === null ? 'none' : $result['oldest_age_seconds'].'s',
            $result['alerted'] ? 'yes' : 'no',
        );

        if ($result['status'] === 'healthy') {
            $this->info($summary);

            return self::SUCCESS;
        }

        $this->error($summary);

        return self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use App\Domain\Health\HealthMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class CheckServiceHealth extends Command
{
    protected $signature = 'ukapi:health:check';

    protected $description = 'Measure service availability and send configured operator alerts.';

    public function handle(HealthMonitor $monitor): int
    {
        if (! config('health.enabled')) {
            $this->comment('Health monitoring is disabled.');

            return self::SUCCESS;
        }

        // Also covers manual command invocations, not just scheduler overlap.
        $lock = Cache::lock('ukapi:health:monitor', 300);
        try {
            if (! $lock->get()) {
                $this->comment('A health check is already running.');

                return self::SUCCESS;
            }
            $outcomes = $monitor->run();
            foreach ($outcomes as $service => $outcome) {
                $this->line($service.': '.$outcome);
            }

            return count(array_filter($outcomes, static fn (string $outcome): bool => $outcome !== 'available')) > 0
                ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Health monitoring could not complete. Check database, cache and migration readiness.');

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}

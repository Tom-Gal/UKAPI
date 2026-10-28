<?php

namespace App\Console\Commands;

use App\Domain\Operations\ProductionReadiness;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;

final class ProductionReadinessCheck extends Command
{
    protected $signature = 'ukapi:production:check {--probe : Also connect to MySQL and Redis}';

    protected $description = 'Check the required production configuration without printing secrets.';

    public function handle(Repository $cache, ProductionReadiness $readiness): int
    {
        $failed = false;
        foreach ($readiness->checks() as $check) {
            if ($check['passed']) {
                $this->info("[ok] {$check['key']}");
            } else {
                $failed = true;
                $this->error("[missing] {$check['key']}: {$check['guidance']}");
            }
        }

        if ($this->option('probe')) {
            $failed = ! $this->probeDependencies($cache) || $failed;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function probeDependencies(Repository $cache): bool
    {
        try {
            DB::select('select 1');
            $this->info('[ok] MySQL connection');
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('[missing] MySQL connection');

            return false;
        }

        try {
            $cache->get('ukapi:health:readiness');
            $this->info('[ok] Redis connection');
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('[missing] Redis connection');

            return false;
        }

        return true;
    }
}

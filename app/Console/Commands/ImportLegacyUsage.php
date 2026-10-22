<?php

namespace App\Console\Commands;

use App\Domain\Usage\UsageMeter;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ImportLegacyUsage extends Command
{
    protected $signature = 'ukapi:usage:import-cache {--store= : Explicit legacy shared cache store (redis or database)}';

    protected $description = 'Import current-month legacy usage during a maintenance window, without reducing durable counters.';

    public function handle(UsageMeter $meter): int
    {
        $store = $this->option('store');
        if (! is_string($store) || ! in_array(config("cache.stores.{$store}.driver"), ['redis', 'database'], true)) {
            $this->error('Choose an explicit shared Redis or database cache store with --store. Failover and array stores are not supported.');

            return self::FAILURE;
        }

        $period = $meter->periodStart();
        $month = $period->toDateString();
        $cache = Cache::store($store);
        /** @var array<string, string> $labels */
        $labels = config('ukapi.usage_endpoint_labels', []);
        $count = 0;
        foreach (User::query()->select('id')->lazyById() as $user) {
            $prefix = sprintf('ukapi:usage:%s:account:%d', $period->format('Ym'), $user->id);
            $total = max(0, (int) $cache->get($prefix, 0));
            $breakdown = [];
            foreach ($labels as $endpoint => $label) {
                $breakdown[$endpoint] = max(0, (int) $cache->get($prefix.':endpoint:'.$endpoint, 0));
            }
            DB::transaction(function () use ($user, $month, $total, $breakdown): void {
                $at = CarbonImmutable::now('UTC');
                DB::table('monthly_usage')->insertOrIgnore([
                    'user_id' => $user->id, 'month' => $month, 'requests' => 0,
                    'created_at' => $at, 'updated_at' => $at,
                ]);
                DB::table('monthly_usage')->where('user_id', $user->id)->where('month', $month)
                    ->where('requests', '<', $total)->update(['requests' => $total, 'updated_at' => $at]);
                foreach ($breakdown as $endpoint => $requests) {
                    if ($requests === 0) {
                        continue;
                    }
                    DB::table('monthly_endpoint_usage')->insertOrIgnore([
                        'user_id' => $user->id, 'month' => $month, 'endpoint' => $endpoint, 'requests' => 0,
                    ]);
                    DB::table('monthly_endpoint_usage')->where('user_id', $user->id)->where('month', $month)
                        ->where('endpoint', $endpoint)->where('requests', '<', $requests)
                        ->update(['requests' => $requests]);
                }
            }, 5);
            $count++;
        }
        $this->info("Imported current-month legacy usage for {$count} account(s). Existing higher counters were preserved.");

        return self::SUCCESS;
    }
}

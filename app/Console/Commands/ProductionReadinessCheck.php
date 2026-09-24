<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;

final class ProductionReadinessCheck extends Command
{
    protected $signature = 'ukapi:production:check {--probe : Also connect to PostgreSQL and Valkey}';

    protected $description = 'Check the required production configuration without printing secrets.';

    public function handle(Repository $cache): int
    {
        $checks = [
            ['APP_ENV', app()->environment('production'), 'Set APP_ENV=production.'],
            ['APP_DEBUG', ! config('app.debug'), 'Set APP_DEBUG=false.'],
            ['APP_KEY', is_string(config('app.key')) && config('app.key') !== '', 'Set a unique APP_KEY.'],
            ['APP_URL', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL to the public HTTPS origin.'],
            ['SESSION_SECURE_COOKIE', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.'],
            ['PostgreSQL', config('database.default') === 'pgsql', 'Set DB_CONNECTION=pgsql.'],
            ['Valkey cache', config('cache.default') === 'redis', 'Set CACHE_STORE=redis and configure Valkey.'],
            ['Mail delivery', ! in_array(config('mail.default'), ['array', 'log'], true), 'Configure a real production mailer.'],
        ];

        $failed = false;
        foreach ($checks as [$name, $passed, $guidance]) {
            if ($passed) {
                $this->info("[ok] {$name}");
            } else {
                $failed = true;
                $this->error("[missing] {$name}: {$guidance}");
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
            $this->info('[ok] PostgreSQL connection');
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('[missing] PostgreSQL connection');

            return false;
        }

        try {
            $cache->get('ukapi:health:readiness');
            $this->info('[ok] Valkey connection');
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('[missing] Valkey connection');

            return false;
        }

        return true;
    }
}

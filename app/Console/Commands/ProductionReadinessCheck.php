<?php

namespace App\Console\Commands;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;

final class ProductionReadinessCheck extends Command
{
    protected $signature = 'ukapi:production:check {--probe : Also connect to MySQL and Valkey}';

    protected $description = 'Check the required production configuration without printing secrets.';

    public function handle(Repository $cache, PlanCatalog $plans): int
    {
        $checks = [
            ['APP_ENV', app()->environment('production'), 'Set APP_ENV=production.'],
            ['APP_DEBUG', ! config('app.debug'), 'Set APP_DEBUG=false.'],
            ['APP_KEY', is_string(config('app.key')) && config('app.key') !== '', 'Set a unique APP_KEY.'],
            ['APP_URL', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL to the public HTTPS origin.'],
            ['SESSION_SECURE_COOKIE', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.'],
            ['MySQL', config('database.default') === 'mysql', 'Set DB_CONNECTION=mysql.'],
            ['Valkey cache', config('cache.default') === 'redis', 'Set CACHE_STORE=redis and configure Valkey.'],
            ['Mail delivery', ! in_array(config('mail.default'), ['array', 'log'], true), 'Configure a real production mailer.'],
            ['Stripe secret key', filled(config('cashier.secret')), 'Set STRIPE_SECRET in the secret manager.'],
            ['Stripe publishable key', filled(config('cashier.key')), 'Set STRIPE_KEY in the secret manager.'],
            ['Stripe webhook secret', filled(config('cashier.webhook.secret')), 'Create the Stripe webhook and set STRIPE_WEBHOOK_SECRET.'],
            ['Stripe plan prices', $plans->hasConfiguredPrices(), 'Set STRIPE_PRICE_HOBBY, STRIPE_PRICE_PRO, and STRIPE_PRICE_SCALE.'],
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
            $this->info('[ok] MySQL connection');
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('[missing] MySQL connection');

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

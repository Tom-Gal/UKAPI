<?php

namespace App\Console\Commands;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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
            ['Legal entity name', $this->validLegalValue(config('legal.entity_name')), 'Set LEGAL_ENTITY_NAME to the approved legal or trading identity.'],
            ['Legal registered office', $this->validLegalValue(config('legal.registered_office')), 'Set LEGAL_REGISTERED_OFFICE to the approved postal address.'],
            ['Legal contact email', filter_var(config('legal.contact_email'), FILTER_VALIDATE_EMAIL) !== false, 'Set LEGAL_CONTACT_EMAIL to the approved contact address.'],
            ['Legal company number', $this->validOptionalLegalValue(config('legal.company_number')), 'Set LEGAL_COMPANY_NUMBER to the approved value or leave it empty when it does not apply.'],
            ['Legal ICO number', $this->validOptionalLegalValue(config('legal.ico_number')), 'Set LEGAL_ICO_NUMBER to the approved value or leave it empty when it does not apply.'],
            ['Companies House credential', filled(config('ukapi.companies_house.api_key')), 'Set COMPANIES_HOUSE_API_KEY in the secret manager.'],
            ['MOT client ID', filled(config('ukapi.mot_history.client_id')), 'Set MOT_HISTORY_CLIENT_ID in the secret manager.'],
            ['MOT client secret', filled(config('ukapi.mot_history.client_secret')), 'Set MOT_HISTORY_CLIENT_SECRET in the secret manager.'],
            ['MOT API key', filled(config('ukapi.mot_history.api_key')), 'Set MOT_HISTORY_API_KEY in the secret manager.'],
            ['MOT scope URL', filter_var(config('ukapi.mot_history.scope'), FILTER_VALIDATE_URL) !== false, 'Set MOT_HISTORY_SCOPE_URL to the scope issued by DVSA.'],
            ['MOT token URL', filter_var(config('ukapi.mot_history.token_url'), FILTER_VALIDATE_URL) !== false, 'Set MOT_HISTORY_TOKEN_URL to the URL issued by DVSA.'],
            ['Stripe secret key', filled(config('cashier.secret')), 'Set STRIPE_SECRET in the secret manager.'],
            ['Stripe publishable key', filled(config('cashier.key')), 'Set STRIPE_KEY in the secret manager.'],
            ['Stripe webhook secret', filled(config('cashier.webhook.secret')), 'Create the Stripe webhook and set STRIPE_WEBHOOK_SECRET.'],
            ['Stripe webhook route', Route::has('cashier.webhook'), 'Ensure Laravel Cashier is installed and clear/rebuild the route cache.'],
            ['Stripe plan prices', $plans->hasConfiguredPrices(), 'Set STRIPE_PRICE_HOBBY, STRIPE_PRICE_PRO, and STRIPE_PRICE_SCALE.'],
            ['Stripe annual plan prices', $plans->hasConfiguredAnnualPrices(), 'Run php artisan stripe:provision-plans in the applicable Stripe mode.'],
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

    private function validLegalValue(mixed $value): bool
    {
        return is_string($value)
            && trim($value) !== ''
            && ! preg_match('/(?:\[[^]]+\]|placeholder|to be confirmed|tbc|todo)/i', $value);
    }

    private function validOptionalLegalValue(mixed $value): bool
    {
        return $value === null || $value === '' || $this->validLegalValue($value);
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

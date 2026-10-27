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
            ['APP_VERSION', $this->validReleaseIdentifier(config('app.version')), 'Set APP_VERSION to a deployed commit SHA or release identifier, not dev.'],
            ['SESSION_SECURE_COOKIE', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true.'],
            ['MySQL', config('database.default') === 'mysql', 'Set DB_CONNECTION=mysql.'],
            ['Valkey cache', config('cache.default') === 'redis', 'Set CACHE_STORE=redis and configure Valkey.'],
            ['Mail delivery', ! in_array(config('mail.default'), ['array', 'log'], true), 'Configure a real production mailer.'],
            ['CORS and API hosts', $this->corsAndApiHostsAreConsistent(), 'Set UKAPI_API_URL and CORS_ALLOWED_ORIGINS to the HTTPS public web/API origins.'],
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
            ['Health monitoring', (bool) config('health.enabled') || (bool) config('health.waived'), 'Enable HEALTH_MONITORING_ENABLED or explicitly set HEALTH_MONITORING_WAIVED=true.'],
            ['Operator alerts', $this->operatorAlertsAreConfigured(), 'Enable alerts with HEALTH_ALERT_EMAIL or explicitly set HEALTH_ALERTS_WAIVED=true.'],
            ['SIC snapshot storage', $this->sicSnapshotStorageIsReady(), 'Set SIC_REFERENCE_SNAPSHOT_PATH to an existing writable directory when refresh is enabled.'],
            ['Laravel scheduler', (bool) config('ukapi.operations.scheduler_enabled'), 'Set UKAPI_SCHEDULER_ENABLED=true for the Laravel Cloud scheduler.'],
            ['Queue worker expectation', $this->queueWorkerExpectationIsConsistent(), 'Set UKAPI_QUEUE_WORKER_ENABLED to match QUEUE_CONNECTION and the Laravel Cloud worker.'],
            ['Queue failure monitoring', $this->queueMonitorIsConfigured(), 'Enable UKAPI_QUEUE_MONITOR_ENABLED with HEALTH_ALERT_EMAIL, or explicitly waive it.'],
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

    private function validReleaseIdentifier(mixed $value): bool
    {
        return is_string($value)
            && trim($value) !== ''
            && ! preg_match('/^(?:dev|development|local|testing)(?:[-_.].*)?$/i', trim($value));
    }

    private function corsAndApiHostsAreConsistent(): bool
    {
        $applicationOrigin = $this->origin(config('app.url'));
        $apiOrigin = $this->origin(config('ukapi.api_url'));
        $allowedOrigins = config('cors.allowed_origins');

        if ($applicationOrigin === null || $apiOrigin === null || ! is_array($allowedOrigins)) {
            return false;
        }

        $normalisedOrigins = [];
        $knownOrigins = [$applicationOrigin, $apiOrigin];
        foreach ($allowedOrigins as $allowedOrigin) {
            $normalisedOrigin = $this->origin($allowedOrigin);
            if ($normalisedOrigin === null) {
                return false;
            }

            if (! in_array($normalisedOrigin, $knownOrigins, true)) {
                return false;
            }

            if (app()->environment('production') && ! str_starts_with($normalisedOrigin, 'https://')) {
                return false;
            }

            $normalisedOrigins[] = $normalisedOrigin;
        }

        return in_array($applicationOrigin, $normalisedOrigins, true)
            && str_starts_with($applicationOrigin, 'https://')
            && str_starts_with($apiOrigin, 'https://');
    }

    private function origin(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $parts = parse_url(trim($value));
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])
            || ! in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)
            || isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/'
            || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $origin = strtolower((string) $parts['scheme']).'://'.strtolower((string) $parts['host']);
        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }

    private function operatorAlertsAreConfigured(): bool
    {
        if ((bool) config('health.alerts.enabled')) {
            return filter_var(config('health.alerts.email'), FILTER_VALIDATE_EMAIL) !== false
                && (int) config('health.alerts.failure_threshold') > 0;
        }

        return (bool) config('health.alerts.waived');
    }

    private function sicSnapshotStorageIsReady(): bool
    {
        if (! (bool) config('ukapi.sic_reference.refresh_enabled')) {
            return true;
        }

        $snapshotPath = config('ukapi.sic_reference.snapshot_path');
        if (! is_string($snapshotPath) || trim($snapshotPath) === '') {
            return false;
        }

        return is_file($snapshotPath)
            ? is_writable($snapshotPath)
            : is_dir(dirname($snapshotPath)) && is_writable(dirname($snapshotPath));
    }

    private function queueWorkerExpectationIsConsistent(): bool
    {
        $connection = (string) config('queue.default');
        $workerEnabled = (bool) config('ukapi.operations.queue_worker_enabled');

        if ($connection === 'sync') {
            return ! $workerEnabled;
        }

        return $workerEnabled && in_array($connection, ['database', 'redis'], true);
    }

    private function queueMonitorIsConfigured(): bool
    {
        if ((bool) config('ukapi.operations.queue_monitor.enabled')) {
            return config('queue.default') === 'database'
                && filter_var(config('health.alerts.email'), FILTER_VALIDATE_EMAIL) !== false
                && (int) config('ukapi.operations.queue_monitor.max_pending') >= 0
                && (int) config('ukapi.operations.queue_monitor.max_oldest_seconds') > 0;
        }

        return (bool) config('ukapi.operations.queue_monitor.waived');
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

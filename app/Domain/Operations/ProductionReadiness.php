<?php

namespace App\Domain\Operations;

use App\Domain\Billing\PlanCatalog;
use Illuminate\Support\Facades\Route;

final class ProductionReadiness
{
    public function __construct(private readonly PlanCatalog $plans) {}

    /** @return list<array{key: string, passed: bool, guidance: string}> */
    public function checks(): array
    {
        return [
            ['key' => 'APP_ENV', 'passed' => app()->environment('production'), 'guidance' => 'Set APP_ENV=production.'],
            ['key' => 'APP_DEBUG', 'passed' => ! config('app.debug'), 'guidance' => 'Set APP_DEBUG=false.'],
            ['key' => 'APP_KEY', 'passed' => is_string(config('app.key')) && config('app.key') !== '', 'guidance' => 'Set a unique APP_KEY.'],
            ['key' => 'APP_URL', 'passed' => str_starts_with((string) config('app.url'), 'https://'), 'guidance' => 'Set APP_URL to the public HTTPS origin.'],
            ['key' => 'APP_VERSION', 'passed' => $this->validReleaseIdentifier(config('app.version')), 'guidance' => 'Set APP_VERSION to a deployed commit SHA or release identifier, not dev.'],
            ['key' => 'SESSION_SECURE_COOKIE', 'passed' => (bool) config('session.secure'), 'guidance' => 'Set SESSION_SECURE_COOKIE=true.'],
            ['key' => 'MySQL', 'passed' => config('database.default') === 'mysql', 'guidance' => 'Set DB_CONNECTION=mysql.'],
            ['key' => 'Redis cache', 'passed' => config('cache.default') === 'redis', 'guidance' => 'Set CACHE_STORE=redis and configure the attached Redis resource.'],
            ['key' => 'Redis connection configuration', 'passed' => $this->redisConnectionIsConfigured(), 'guidance' => 'Attach the Laravel Cloud Redis resource so REDIS_HOST and REDIS_PASSWORD are injected, or configure a non-local REDIS_URL/REDIS_* connection.'],
            ['key' => 'Mail delivery', 'passed' => ! in_array(config('mail.default'), ['array', 'log'], true), 'guidance' => 'Configure a real production mailer.'],
            ['key' => 'CORS and API hosts', 'passed' => $this->corsAndApiHostsAreConsistent(), 'guidance' => 'Set UKAPI_API_URL and CORS_ALLOWED_ORIGINS to the HTTPS public web/API origins.'],
            ['key' => 'Legal entity name', 'passed' => $this->validLegalValue(config('legal.entity_name')), 'guidance' => 'Set LEGAL_ENTITY_NAME to the approved legal or trading identity.'],
            ['key' => 'Legal registered office', 'passed' => $this->validLegalValue(config('legal.registered_office')), 'guidance' => 'Set LEGAL_REGISTERED_OFFICE to the approved postal address.'],
            ['key' => 'Legal contact email', 'passed' => filter_var(config('legal.contact_email'), FILTER_VALIDATE_EMAIL) !== false, 'guidance' => 'Set LEGAL_CONTACT_EMAIL to the approved contact address.'],
            ['key' => 'Legal company number', 'passed' => $this->validOptionalLegalValue(config('legal.company_number')), 'guidance' => 'Set LEGAL_COMPANY_NUMBER to the approved value or leave it empty when it does not apply.'],
            ['key' => 'Legal ICO number', 'passed' => $this->validOptionalLegalValue(config('legal.ico_number')), 'guidance' => 'Set LEGAL_ICO_NUMBER to the approved value or leave it empty when it does not apply.'],
            ['key' => 'Companies House credential', 'passed' => filled(config('ukapi.companies_house.api_key')), 'guidance' => 'Set COMPANIES_HOUSE_API_KEY in the secret manager.'],
            ['key' => 'MOT client ID', 'passed' => filled(config('ukapi.mot_history.client_id')), 'guidance' => 'Set MOT_HISTORY_CLIENT_ID in the secret manager.'],
            ['key' => 'MOT client secret', 'passed' => filled(config('ukapi.mot_history.client_secret')), 'guidance' => 'Set MOT_HISTORY_CLIENT_SECRET in the secret manager.'],
            ['key' => 'MOT API key', 'passed' => filled(config('ukapi.mot_history.api_key')), 'guidance' => 'Set MOT_HISTORY_API_KEY in the secret manager.'],
            ['key' => 'MOT scope URL', 'passed' => filter_var(config('ukapi.mot_history.scope'), FILTER_VALIDATE_URL) !== false, 'guidance' => 'Set MOT_HISTORY_SCOPE_URL to the scope issued by DVSA.'],
            ['key' => 'MOT token URL', 'passed' => filter_var(config('ukapi.mot_history.token_url'), FILTER_VALIDATE_URL) !== false, 'guidance' => 'Set MOT_HISTORY_TOKEN_URL to the URL issued by DVSA.'],
            ['key' => 'Health monitoring', 'passed' => (bool) config('health.enabled') || (bool) config('health.waived'), 'guidance' => 'Enable HEALTH_MONITORING_ENABLED or explicitly set HEALTH_MONITORING_WAIVED=true.'],
            ['key' => 'Operator alerts', 'passed' => $this->operatorAlertsAreConfigured(), 'guidance' => 'Enable alerts with HEALTH_ALERT_EMAIL or explicitly set HEALTH_ALERTS_WAIVED=true.'],
            ['key' => 'SIC snapshot storage', 'passed' => $this->sicSnapshotStorageIsReady(), 'guidance' => 'Set SIC_REFERENCE_SNAPSHOT_PATH to an existing writable directory when refresh is enabled.'],
            ['key' => 'Laravel scheduler', 'passed' => (bool) config('ukapi.operations.scheduler_enabled'), 'guidance' => 'Set UKAPI_SCHEDULER_ENABLED=true for the Laravel Cloud scheduler.'],
            ['key' => 'Queue execution mode', 'passed' => $this->queueWorkerExpectationIsConsistent(), 'guidance' => 'Without a persistent Laravel Cloud worker, set QUEUE_CONNECTION=sync and UKAPI_QUEUE_WORKER_ENABLED=false. Otherwise enable the matching database or Redis worker.'],
            ['key' => 'Queue backlog monitoring', 'passed' => $this->queueMonitorIsConfigured(), 'guidance' => 'Enable database queue monitoring with HEALTH_ALERT_EMAIL when a worker exists, or set UKAPI_QUEUE_MONITOR_WAIVED=true for the intentional sync fallback.'],
            ['key' => 'Stripe secret key', 'passed' => filled(config('cashier.secret')), 'guidance' => 'Set STRIPE_SECRET in the secret manager.'],
            ['key' => 'Stripe publishable key', 'passed' => filled(config('cashier.key')), 'guidance' => 'Set STRIPE_KEY in the secret manager.'],
            ['key' => 'Stripe webhook secret', 'passed' => filled(config('cashier.webhook.secret')), 'guidance' => 'Create the Stripe webhook and set STRIPE_WEBHOOK_SECRET.'],
            ['key' => 'Stripe webhook route', 'passed' => Route::has('cashier.webhook'), 'guidance' => 'Ensure Laravel Cashier is installed and routes are cached correctly.'],
            ['key' => 'Stripe plan prices', 'passed' => $this->plans->hasConfiguredPrices(), 'guidance' => 'Set all monthly Stripe plan prices.'],
            ['key' => 'Stripe annual plan prices', 'passed' => $this->plans->hasConfiguredAnnualPrices(), 'guidance' => 'Run php artisan stripe:provision-plans in the applicable Stripe mode.'],
        ];
    }

    /** @return array<string, string|bool> */
    public function runtime(): array
    {
        return [
            'environment' => (string) app()->environment(),
            'version' => (string) config('app.version'),
            'debug' => (bool) config('app.debug'),
            'app_url' => $this->origin(config('app.url')) ?? 'Invalid or unset',
            'api_url' => $this->origin(config('ukapi.api_url')) ?? 'Invalid or unset',
            'database' => (string) config('database.default'),
            'cache' => (string) config('cache.default'),
            'queue' => (string) config('queue.default'),
            'mail' => (string) config('mail.default'),
            'health_monitoring' => (bool) config('health.enabled'),
            'scheduler' => (bool) config('ukapi.operations.scheduler_enabled'),
            'queue_worker' => (bool) config('ukapi.operations.queue_worker_enabled'),
        ];
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
        foreach ($allowedOrigins as $allowedOrigin) {
            $normalisedOrigin = $this->origin($allowedOrigin);
            if ($normalisedOrigin === null || ! in_array($normalisedOrigin, [$applicationOrigin, $apiOrigin], true)) {
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

    private function redisConnectionIsConfigured(): bool
    {
        $store = config('cache.stores.redis');
        if (! is_array($store)) {
            return false;
        }

        $connections = array_unique([
            $store['connection'] ?? 'cache',
            $store['lock_connection'] ?? 'default',
        ]);

        foreach ($connections as $connectionName) {
            if (! is_string($connectionName)) {
                return false;
            }

            $connection = config("database.redis.{$connectionName}");
            if (! is_array($connection) || ! $this->redisConnectionHasRemoteEndpoint($connection)) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $connection */
    private function redisConnectionHasRemoteEndpoint(array $connection): bool
    {
        $url = $connection['url'] ?? null;
        if (is_string($url) && trim($url) !== '') {
            $host = parse_url($url, PHP_URL_HOST);

            return is_string($host) && ! in_array(strtolower($host), ['127.0.0.1', 'localhost'], true);
        }

        $host = $connection['host'] ?? null;
        $password = $connection['password'] ?? null;

        return is_string($host)
            && trim($host) !== ''
            && ! in_array(strtolower(trim($host)), ['127.0.0.1', 'localhost'], true)
            && is_string($password)
            && trim($password) !== '';
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
}

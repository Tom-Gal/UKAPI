<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustConfiguredProxies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_endpoint_checks_dependencies_and_is_not_cacheable(): void
    {
        config()->set('app.version', 'beta-test');

        $response = $this->getJson('/ready')
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'version' => 'beta-test',
            ])
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_grant_admin_command_only_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'operator@example.com']);

        $this->artisan('ukapi:grant-admin', ['email' => 'OPERATOR@EXAMPLE.COM'])
            ->expectsOutput('Administrator role granted.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_admin' => true]);

        $this->artisan('ukapi:grant-admin', ['email' => 'unknown@example.com'])
            ->expectsOutput('No user was found for the supplied email address.')
            ->assertFailed();
    }

    public function test_trusted_ingress_marks_forwarded_https_requests_as_secure(): void
    {
        config()->set('app.trusted_proxies', '*');
        $request = Request::create('/', 'GET', server: [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'REMOTE_ADDR' => '10.0.0.1',
        ]);

        (new TrustConfiguredProxies)->handle($request, static fn () => response('ok'));

        $this->assertTrue($request->isSecure());
    }

    public function test_production_check_accepts_consistent_legal_redis_and_provider_configuration(): void
    {
        $mysql = config('database.connections.mysql');

        try {
            $this->configureProduction();

            $this->artisan('ukapi:production:check')
                ->expectsOutput('[ok] Redis cache')
                ->expectsOutput('[ok] Redis connection configuration')
                ->expectsOutput('[ok] Legal entity name')
                ->expectsOutput('[ok] Legal registered office')
                ->expectsOutput('[ok] MOT token URL')
                ->assertSuccessful();
        } finally {
            $this->restoreTestingConfiguration($mysql);
        }
    }

    public function test_production_check_rejects_known_legal_placeholders(): void
    {
        $mysql = config('database.connections.mysql');

        try {
            $this->configureProduction();
            config()->set('legal.entity_name', '[FULL LEGAL NAME]');
            config()->set('legal.registered_office', 'TBC');
            config()->set('legal.company_number', '[COMPANY NUMBER]');

            $this->artisan('ukapi:production:check')
                ->expectsOutput('[missing] Legal entity name: Set LEGAL_ENTITY_NAME to the approved legal or trading identity.')
                ->expectsOutput('[missing] Legal registered office: Set LEGAL_REGISTERED_OFFICE to the approved postal address.')
                ->expectsOutput('[missing] Legal company number: Set LEGAL_COMPANY_NUMBER to the approved value or leave it empty when it does not apply.')
                ->assertFailed();
        } finally {
            $this->restoreTestingConfiguration($mysql);
        }
    }

    public function test_production_check_rejects_untracked_release_and_operational_configuration(): void
    {
        $mysql = config('database.connections.mysql');

        try {
            $this->configureProduction();
            config()->set([
                'app.version' => 'dev',
                'cors.allowed_origins' => ['https://api.ukapi.co.uk'],
                'health.enabled' => false,
                'health.waived' => false,
                'health.alerts.enabled' => false,
                'health.alerts.waived' => false,
                'ukapi.sic_reference.refresh_enabled' => true,
                'ukapi.sic_reference.snapshot_path' => base_path('tests/Fixtures/missing-sic-directory/sic.json'),
                'ukapi.operations.scheduler_enabled' => false,
                'ukapi.operations.queue_worker_enabled' => false,
                'ukapi.operations.queue_monitor.enabled' => false,
                'ukapi.operations.queue_monitor.waived' => false,
                'queue.default' => 'database',
            ]);

            $this->artisan('ukapi:production:check')
                ->expectsOutput('[missing] APP_VERSION: Set APP_VERSION to a deployed commit SHA or release identifier, not dev.')
                ->expectsOutput('[missing] CORS and API hosts: Set UKAPI_API_URL and CORS_ALLOWED_ORIGINS to the HTTPS public web/API origins.')
                ->expectsOutput('[missing] Health monitoring: Enable HEALTH_MONITORING_ENABLED or explicitly set HEALTH_MONITORING_WAIVED=true.')
                ->expectsOutput('[missing] Operator alerts: Enable alerts with HEALTH_ALERT_EMAIL or explicitly set HEALTH_ALERTS_WAIVED=true.')
                ->expectsOutput('[missing] SIC snapshot storage: Set SIC_REFERENCE_SNAPSHOT_PATH to an existing writable directory when refresh is enabled.')
                ->expectsOutput('[missing] Laravel scheduler: Set UKAPI_SCHEDULER_ENABLED=true for the Laravel Cloud scheduler.')
                ->expectsOutput('[missing] Queue execution mode: Without a persistent Laravel Cloud worker, set QUEUE_CONNECTION=sync and UKAPI_QUEUE_WORKER_ENABLED=false. Otherwise enable the matching database or Redis worker.')
                ->expectsOutput('[missing] Queue backlog monitoring: Enable database queue monitoring with HEALTH_ALERT_EMAIL when a worker exists, or set UKAPI_QUEUE_MONITOR_WAIVED=true for the intentional sync fallback.')
                ->assertFailed();
        } finally {
            $this->restoreTestingConfiguration($mysql);
        }
    }

    public function test_production_check_accepts_the_intentional_sync_no_worker_mode(): void
    {
        $mysql = config('database.connections.mysql');

        try {
            $this->configureProduction();
            config()->set([
                'queue.default' => 'sync',
                'ukapi.operations.queue_worker_enabled' => false,
                'ukapi.operations.queue_monitor.enabled' => false,
                'ukapi.operations.queue_monitor.waived' => true,
            ]);

            $this->artisan('ukapi:production:check')
                ->expectsOutput('[ok] Health monitoring')
                ->expectsOutput('[ok] Operator alerts')
                ->expectsOutput('[ok] Queue execution mode')
                ->expectsOutput('[ok] Queue backlog monitoring')
                ->assertSuccessful();
        } finally {
            $this->restoreTestingConfiguration($mysql);
        }
    }

    private function configureProduction(): void
    {
        $this->app['env'] = 'production';
        config()->set([
            'app.debug' => false,
            'app.key' => 'base64:test-application-key',
            'app.url' => 'https://ukapi.co.uk',
            'app.version' => 'release-2026-10-26',
            'session.secure' => true,
            'database.default' => 'mysql',
            // Exercise the production connection name without reaching a live
            // MySQL host from the test suite.
            'database.connections.mysql' => config('database.connections.sqlite'),
            'database.redis.default.host' => 'redis.internal.test',
            'database.redis.default.password' => 'redis-password',
            'database.redis.cache.host' => 'redis.internal.test',
            'database.redis.cache.password' => 'redis-password',
            'cache.default' => 'redis',
            'mail.default' => 'smtp',
            'cors.allowed_origins' => ['https://ukapi.co.uk'],
            'ukapi.api_url' => 'https://api.ukapi.co.uk',
            'health.enabled' => true,
            'health.waived' => false,
            'health.alerts.enabled' => true,
            'health.alerts.waived' => false,
            'health.alerts.email' => 'alerts@ukapi.co.uk',
            'ukapi.sic_reference.refresh_enabled' => false,
            'ukapi.operations.scheduler_enabled' => true,
            'ukapi.operations.queue_worker_enabled' => true,
            'ukapi.operations.queue_monitor.enabled' => true,
            'ukapi.operations.queue_monitor.waived' => false,
            'ukapi.operations.queue_monitor.max_pending' => 100,
            'ukapi.operations.queue_monitor.max_oldest_seconds' => 300,
            'queue.default' => 'database',
            'legal.entity_name' => 'Approved Operator Name',
            'legal.registered_office' => '1 Example Street, London, SW1A 1AA',
            'legal.contact_email' => 'contact@ukapi.co.uk',
            'legal.company_number' => null,
            'legal.ico_number' => null,
            'ukapi.companies_house.api_key' => 'company-secret',
            'ukapi.mot_history.client_id' => 'mot-client-id',
            'ukapi.mot_history.client_secret' => 'mot-client-secret',
            'ukapi.mot_history.api_key' => 'mot-api-key',
            'ukapi.mot_history.token_url' => 'https://login.example.test/oauth2/token',
            'cashier.key' => 'pk_live_example',
            'cashier.secret' => 'sk_live_example',
            'cashier.webhook.secret' => 'whsec_example',
            'ukapi.paid_plans.hobby.stripe_price' => 'price_hobby',
            'ukapi.paid_plans.hobby.annual_stripe_price' => 'price_hobby_year',
            'ukapi.paid_plans.pro.stripe_price' => 'price_pro',
            'ukapi.paid_plans.pro.annual_stripe_price' => 'price_pro_year',
            'ukapi.paid_plans.scale.stripe_price' => 'price_scale',
            'ukapi.paid_plans.scale.annual_stripe_price' => 'price_scale_year',
        ]);
    }

    /** @param array<string, mixed> $mysql */
    private function restoreTestingConfiguration(array $mysql): void
    {
        $this->app['env'] = 'testing';
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.mysql', $mysql);
        DB::purge('mysql');
    }
}

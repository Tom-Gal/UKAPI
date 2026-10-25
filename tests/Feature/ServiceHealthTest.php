<?php

namespace Tests\Feature;

use App\Domain\Health\HealthAlert;
use App\Domain\Health\HealthAlerts;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\ServiceHealthCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ServiceHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 22)->startOfDay());
        config()->set([
            'health.enabled' => true,
            'health.alerts.enabled' => false,
            'health.stale_after_seconds' => 900,
            'health.crime_source_max_age_days' => 120,
            'health.sic_retrieval_max_age_days' => 90,
            'ukapi.companies_house.api_key' => 'test-secret-do-not-publish',
            'ukapi.mot_history.api_key' => 'mot-api-key-do-not-publish',
            'ukapi.mot_history.client_id' => 'mot-client-id',
            'ukapi.mot_history.client_secret' => 'mot-client-secret-do-not-publish',
            'ukapi.mot_history.access_token' => null,
            'ukapi.mot_history.token_url' => 'https://login.example.test/oauth2/token',
            'ukapi.mot_history.scope' => 'https://mot.example.test/.default',
            'ukapi.sic_reference.snapshot_path' => base_path('tests/Fixtures/CompaniesHouse/sic-reference.json'),
            'ukapi.sic_reference.seed_snapshot_path' => null,
        ]);
        Http::preventStrayRequests();
        Notification::fake();
    }

    public function test_initial_status_is_unknown_without_probing_even_when_credentials_are_configured(): void
    {
        $response = $this->publicStatus()->assertOk();
        foreach (range(0, 6) as $index) {
            $response->assertJsonPath("props.services.{$index}.state", 'unknown')
                ->assertJsonPath("props.services.{$index}.last_success_at", null);
        }
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        Http::assertNothingSent();
    }

    public function test_admin_provider_register_includes_mot_configuration_and_observed_health(): void
    {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get('/admin/providers')
            ->assertOk()
            ->assertJsonPath('props.providers.4.code', 'dvsa_mot_history')
            ->assertJsonPath('props.providers.4.configuration', 'Credential set')
            ->assertJsonPath('props.providers.4.health', 'unknown');
    }

    public function test_checks_persist_validated_results_and_distinguish_source_and_retrieval_age(): void
    {
        config()->set('health.sic_retrieval_max_age_days', 20);
        $this->fakeProviders();
        $this->artisan('ukapi:health:check')->assertSuccessful();
        $this->assertDatabaseCount('service_health_checks', 7);
        foreach (ServiceHealthCheck::all() as $check) {
            $this->assertSame('available', $check->status);
            $this->assertNotNull($check->last_attempt_at);
            $this->assertNotNull($check->last_checked_at);
            $this->assertNotNull($check->last_success_at);
        }
        $this->publicStatus()->assertJsonPath('props.services.1.source_freshness', 'unknown')
            ->assertJsonPath('props.services.2.source_freshness', 'fresh')
            ->assertJsonPath('props.services.2.source_date', '2026-09')
            ->assertJsonPath('props.services.5.state', 'available')
            ->assertJsonPath('props.services.6.source_freshness', 'unknown')
            ->assertJsonPath('props.services.6.reference_freshness', 'stale');
        Http::assertSentCount(7);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'search/companies')
            && $request['items_per_page'] === 1
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('test-secret-do-not-publish:')));
    }

    public function test_failed_checks_preserve_last_success_and_do_not_leak_provider_payloads(): void
    {
        $this->fakeProviders();
        $this->artisan('ukapi:health:check')->assertSuccessful();
        $previousSuccess = ServiceHealthCheck::where('service', 'postcodes')->firstOrFail()->last_success_at;
        $this->travel(5)->minutes();
        $this->fakeProviders(postcodes: Http::response(['secret' => 'private-upstream-error'], 503));
        $this->artisan('ukapi:health:check')->assertFailed();

        $check = ServiceHealthCheck::where('service', 'postcodes')->firstOrFail();
        $this->assertTrue($check->last_success_at->equalTo($previousSuccess));
        $this->assertTrue($check->last_attempt_at->gt($previousSuccess));
        $this->assertSame(1, $check->consecutive_failures);
        $response = $this->publicStatus()->assertJsonPath('props.services.1.state', 'unavailable');
        foreach (['test-secret-do-not-publish', 'private-upstream-error', 'Authorization', 'incident_notified_at', 'snapshot_path'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
    }

    public function test_stopped_scheduler_and_interrupted_attempt_cannot_leave_green_status(): void
    {
        $this->fakeProviders();
        $this->artisan('ukapi:health:check')->assertSuccessful();
        ServiceHealthCheck::where('service', 'postcodes')->update(['status' => 'unknown']);
        $this->publicStatus()->assertJsonPath('props.services.1.state', 'unknown');
        $this->travel(16)->minutes();
        $response = $this->publicStatus();
        foreach (range(0, 6) as $index) {
            $response->assertJsonPath("props.services.{$index}.state", 'stale');
        }
        $response->assertJsonPath('props.services.2.source_freshness', 'unknown');
    }

    public function test_old_crime_data_is_stale_while_the_provider_remains_available(): void
    {
        $this->fakeProviders(source: Http::response(['date' => '2025-01-01']));
        $this->artisan('ukapi:health:check')->assertSuccessful();
        $this->publicStatus()->assertJsonPath('props.services.2.state', 'available')
            ->assertJsonPath('props.services.2.source_freshness', 'stale');
    }

    public function test_freshness_metadata_failure_is_unknown_without_falsely_failing_availability(): void
    {
        $this->fakeProviders(source: Http::response(['date' => '2099-01-01']));
        $this->artisan('ukapi:health:check')->assertSuccessful();
        $this->publicStatus()->assertJsonPath('props.services.2.state', 'available')
            ->assertJsonPath('props.services.2.source_freshness', 'unknown');
        $this->fakeProviders(source: Http::response('bad gateway', 502));
        $this->artisan('ukapi:health:check')->assertSuccessful();
        $this->publicStatus()->assertJsonPath('props.services.2.source_date', null);
    }

    public function test_invalid_payloads_timeouts_and_missing_credentials_fail_without_stopping_other_checks(): void
    {
        config()->set('ukapi.companies_house.api_key', '   ');
        $this->fakeProviders(
            postcodes: Http::failedConnection('private connection detail'),
            crimeCategories: Http::response(['error' => 'invalid']),
        );
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->publicStatus()->assertJsonPath('props.services.1.state', 'unavailable')
            ->assertJsonPath('props.services.2.state', 'unavailable')
            ->assertJsonPath('props.services.3.state', 'available')
            ->assertJsonPath('props.services.4.state', 'unavailable')
            ->assertJsonPath('props.services.5.state', 'available')
            ->assertJsonPath('props.services.6.state', 'available');
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'search/companies'));
    }

    public function test_provider_budget_is_shared_with_customer_integrations(): void
    {
        $this->fakeProviders();
        config()->set('ukapi.companies_house.provider_request_limit', 1);
        $window = (int) config('ukapi.companies_house.provider_limit_window_seconds');
        RateLimiter::hit('ukapi:provider:companies_house:'.intdiv(now()->getTimestamp(), $window), $window);
        $this->artisan('ukapi:health:check')->assertFailed();
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'search/companies'));
    }

    public function test_mot_health_probe_uses_the_customer_provider_budget(): void
    {
        $this->fakeProviders();
        config()->set('ukapi.mot_history.provider_request_limit', 1);
        $window = (int) config('ukapi.mot_history.provider_limit_window_seconds');
        RateLimiter::hit('ukapi:provider:mot_history:'.intdiv(now()->getTimestamp(), $window), $window);

        $this->artisan('ukapi:health:check')->assertFailed();

        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'probe_capacity',
        ]);
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'bulk-download'));
    }

    public function test_mot_probe_distinguishes_configuration_token_credentials_and_api_failures_safely(): void
    {
        config()->set('ukapi.mot_history.api_key', null);
        $this->fakeProviders();
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'configuration_missing',
        ]);

        config()->set('ukapi.mot_history.api_key', 'mot-api-key-do-not-publish');
        Cache::flush();
        $this->fakeProviders(motToken: Http::response(['error' => 'invalid_client'], 401));
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'credentials_rejected',
        ]);

        Cache::flush();
        $this->fakeProviders(motToken: Http::response(['error' => 'temporarily_unavailable'], 503));
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'token_unavailable',
        ]);

        Cache::flush();
        $this->fakeProviders(motToken: Http::response(['expires_in' => 3600]));
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'token_failure',
        ]);

        Cache::flush();
        $this->fakeProviders(motApi: Http::response(['private' => 'provider-body-secret'], 503));
        $this->artisan('ukapi:health:check')->assertFailed();
        $this->assertDatabaseHas('service_health_checks', [
            'service' => 'mot',
            'failure_reason' => 'api_unavailable',
        ]);
        $response = $this->publicStatus()
            ->assertJsonPath('props.services.5.detail', 'Check failed: provider unavailable');
        foreach (['mot-api-key-do-not-publish', 'mot-client-secret-do-not-publish', 'health-token-do-not-publish', 'provider-body-secret'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }
    }

    public function test_alerts_use_durable_threshold_dedup_reminders_and_recovery(): void
    {
        $this->enableAlerts();
        $this->fakeProviders(postcodes: Http::response([], 503));
        $this->artisan('ukapi:health:check')->assertFailed();
        Notification::assertNothingSent();
        $this->artisan('ukapi:health:check')->assertFailed();
        Notification::assertSentOnDemand(HealthAlert::class, fn (HealthAlert $alert, array $channels, object $notifiable): bool => $alert->service === 'postcodes' && ! $alert->recovered && $notifiable->routes['mail'] === 'operator@example.com');
        Notification::assertCount(1);
        Cache::flush();
        $this->artisan('ukapi:health:check')->assertFailed();
        Notification::assertCount(1);
        $this->travel(61)->minutes();
        $this->artisan('ukapi:health:check')->assertFailed();
        Notification::assertCount(2);
        $this->fakeProviders();
        $this->artisan('ukapi:health:check')->assertSuccessful();
        Notification::assertSentOnDemand(HealthAlert::class, fn (HealthAlert $alert): bool => $alert->recovered);
        Notification::assertCount(3);
        $this->artisan('ukapi:health:check')->assertSuccessful();
        Notification::assertCount(3);
        $this->assertNull(ServiceHealthCheck::where('service', 'postcodes')->firstOrFail()->incident_notified_at);
    }

    public function test_delivery_failure_is_retryable_and_does_not_erase_measurements(): void
    {
        $this->enableAlerts();
        config()->set('health.alerts.failure_threshold', 1);
        $this->fakeProviders(postcodes: Http::response([], 503));
        $alerts = \Mockery::mock(HealthAlerts::class);
        $alerts->shouldReceive('send')->times(7)->andReturnUsing(function (ServiceHealthCheck $check): void {
            if ($check->service === 'postcodes') {
                throw new \RuntimeException('private SMTP detail');
            }
        });
        $this->app->instance(HealthAlerts::class, $alerts);
        $this->artisan('ukapi:health:check')->expectsOutput('postcodes: alert_delivery_failed')->assertFailed();
        $check = ServiceHealthCheck::where('service', 'postcodes')->firstOrFail();
        $this->assertNull($check->incident_notified_at);
        $this->assertSame('unavailable', $check->status);
        $this->assertSame('available', ServiceHealthCheck::where('service', 'sic')->firstOrFail()->status);
        $this->app->forgetInstance(HealthAlerts::class);
        $this->artisan('ukapi:health:check')->assertFailed();
        Notification::assertCount(1);
    }

    public function test_disabled_monitor_and_concurrent_invocation_do_not_probe(): void
    {
        config()->set('health.enabled', false);
        $this->artisan('ukapi:health:check')->assertSuccessful();
        config()->set('health.enabled', true);
        $lock = Cache::lock('ukapi:health:monitor', 300);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('ukapi:health:check')->expectsOutput('A health check is already running.')->assertSuccessful();
            $this->assertTrue($lock->isOwnedByCurrentProcess());
            $this->assertDatabaseCount('service_health_checks', 0);
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_missing_health_storage_returns_unknown_public_status_and_nonzero_command(): void
    {
        Schema::drop('service_health_checks');
        $this->publicStatus()->assertOk()->assertJsonPath('props.services.0.state', 'unknown');
        $this->artisan('ukapi:health:check')->assertFailed();
        Http::assertNothingSent();
    }

    private function enableAlerts(): void
    {
        config()->set([
            'health.alerts.enabled' => true,
            'health.alerts.email' => 'operator@example.com',
            'health.alerts.failure_threshold' => 2,
            'health.alerts.repeat_after_minutes' => 60,
            'health.alerts.recovery' => true,
        ]);
    }

    private function fakeProviders(
        mixed $postcodes = null,
        mixed $source = null,
        mixed $crimeCategories = null,
        mixed $motToken = null,
        mixed $motApi = null,
    ): void {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake([
            '*postcodes/SW1A1AA' => $postcodes ?? Http::response($this->fixture('PostcodesIo/lookup-success.json')),
            '*crime-categories*' => $crimeCategories ?? Http::response($this->fixture('PoliceUk/crime-categories.json')),
            '*crime-last-updated*' => $source ?? Http::response(['date' => '2026-09-01']),
            '*id/floods*' => Http::response(['items' => []]),
            '*search/companies*' => Http::response($this->fixture('CompaniesHouse/company-search.json')),
            'https://login.example.test/oauth2/token' => $motToken ?? Http::response([
                'access_token' => 'health-token-do-not-publish',
                'expires_in' => 3600,
            ]),
            '*v1/trade/vehicles/bulk-download' => $motApi ?? Http::response(['bulk' => [], 'delta' => []]),
        ]);
    }

    /** @return array<string, mixed>|list<mixed> */
    private function fixture(string $path): array
    {
        return json_decode(file_get_contents(base_path('tests/Fixtures/'.$path)), true, flags: JSON_THROW_ON_ERROR);
    }

    private function publicStatus(): TestResponse
    {
        return $this->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get('/status');
    }
}

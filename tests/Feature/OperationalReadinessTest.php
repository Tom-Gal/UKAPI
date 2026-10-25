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

    public function test_production_check_accepts_consistent_legal_valkey_and_provider_configuration(): void
    {
        $mysql = config('database.connections.mysql');

        try {
            $this->configureProduction();

            $this->artisan('ukapi:production:check')
                ->expectsOutput('[ok] Valkey cache')
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

    private function configureProduction(): void
    {
        $this->app['env'] = 'production';
        config()->set([
            'app.debug' => false,
            'app.key' => 'base64:test-application-key',
            'app.url' => 'https://ukapi.co.uk',
            'session.secure' => true,
            'database.default' => 'mysql',
            // Exercise the production connection name without reaching a live
            // MySQL host from the test suite.
            'database.connections.mysql' => config('database.connections.sqlite'),
            'cache.default' => 'redis',
            'mail.default' => 'smtp',
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

<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustConfiguredProxies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
}

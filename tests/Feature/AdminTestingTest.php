<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTestingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_testing_page_exposes_safe_diagnostics_without_secrets(): void
    {
        config()->set([
            'app.version' => 'release-test',
            'ukapi.api_url' => 'https://api.example.test',
            'ukapi.companies_house.api_key' => 'companies-house-secret',
            'ukapi.mot_history.client_secret' => 'mot-client-secret',
            'cashier.secret' => 'stripe-secret',
        ]);
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get(route('admin.testing'));

        $response->assertOk()
            ->assertJsonPath('component', 'admin/testing')
            ->assertJsonPath('props.runtime.api_url', 'https://api.example.test')
            ->assertJsonPath('props.runtime.version', 'release-test')
            ->assertJsonPath('props.readinessChecks.0.key', 'APP_ENV')
            ->assertJsonPath('props.healthResults', null);

        foreach (['companies-house-secret', 'mot-client-secret', 'stripe-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    public function test_manual_health_checks_report_when_monitoring_is_disabled(): void
    {
        config()->set('health.enabled', false);
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->post(route('admin.testing.health'))
            ->assertRedirect(route('admin.testing'))
            ->assertSessionHas('status', 'Health monitoring is disabled.');
    }
}

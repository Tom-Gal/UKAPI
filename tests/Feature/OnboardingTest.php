<?php

namespace Tests\Feature;

use App\Domain\Usage\UsageMeter;
use App\Http\Middleware\TrackFirstApiSuccess;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        $this->travelTo('2026-10-22 12:00:00');
    }

    public function test_registration_verification_key_creation_and_first_request_complete_onboarding_permanently(): void
    {
        $this->post(route('register.store'), [
            'name' => 'First Developer',
            'email' => 'onboarding@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'onboarding@example.com')->firstOrFail();
        $this->assertNull($user->first_api_success_at);
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]))->assertRedirect();

        $this->assertDashboard($user, null, 0);
        [$key, $token] = $this->createKey($user);
        $this->get(route('api-keys.index'))->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/api-keys')
            ->has('apiKeys', 1)
            ->where('flash.api_key_created.token', $token));
        $this->get(route('api-keys.index'))->assertInertia(fn (Assert $page) => $page
            ->where('flash.api_key_created', null));

        $headers = ['Authorization' => "Bearer {$token}"];
        $this->getJson('/v1/postcodes/not-a-postcode', $headers)->assertUnprocessable();
        $this->assertNotNull($key->fresh()->last_used_at);
        $this->assertDashboard($user, null, 1);

        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response(
                json_decode(file_get_contents(base_path('tests/Fixtures/PostcodesIo/lookup-success.json')), true, flags: JSON_THROW_ON_ERROR),
            ),
        ]);
        $this->getJson('/v1/postcodes/BL2%206XX', $headers)->assertOk();
        $firstSuccess = $user->fresh()->first_api_success_at;
        $this->assertNotNull($firstSuccess);
        $this->assertTrue($firstSuccess->equalTo(now()));
        $this->assertDashboard($user, $firstSuccess->toIso8601String(), 2);

        // A cached response and another key must not replace the first success.
        $this->travel(2)->seconds();
        $this->getJson('/v1/postcodes/BL2%206XX', $headers)
            ->assertOk()->assertJsonPath('meta.cached', true);
        [, $liveToken] = $this->createKey($user, 'live');
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$liveToken}",
        ])->assertOk();
        $this->assertDashboard($user, $firstSuccess->toIso8601String(), 4);

        $this->travelTo('2026-11-01 00:00:00');
        Cache::flush();
        $this->assertDashboard($user, $firstSuccess->toIso8601String(), 0);
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)->assertOk();
        $this->assertDashboard($user, $firstSuccess->toIso8601String(), 1);

        foreach ($user->apiKeys()->get() as $apiKey) {
            $this->actingAs($user->fresh())->delete(route('api-keys.destroy', $apiKey))->assertRedirect();
        }
        $this->assertDashboard($user, $firstSuccess->toIso8601String(), 1);
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)->assertUnauthorized();
        $this->assertTrue($user->fresh()->first_api_success_at->equalTo($firstSuccess));
    }

    public function test_missing_invalid_and_revoked_keys_and_public_pages_do_not_complete_onboarding(): void
    {
        $user = User::factory()->create();
        [$key, $token] = $this->createKey($user);

        $this->get('/docs')->assertOk();
        $this->get('/openapi/v1.yaml')->assertOk();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20')->assertUnauthorized();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}wrong",
        ])->assertUnauthorized();
        $this->delete(route('api-keys.destroy', $key))->assertRedirect();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertUnauthorized();

        $this->assertDashboard($user, null, 0);
    }

    public function test_feature_and_quota_denials_do_not_complete_onboarding(): void
    {
        config()->set('ukapi.free_plan.monthly_request_quota', 2);
        $user = User::factory()->create();
        [$key, $token] = $this->createKey($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/sic/changes', $headers)->assertForbidden();
        $this->assertDashboard($user, null, 1);

        $this->assertSame(2, app(UsageMeter::class)->reserve($key, 'vat.calculate', 2));
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)
            ->assertTooManyRequests()->assertJsonPath('error.code', 'quota_exceeded');
        $this->assertDashboard($user, null, 2);
    }

    public function test_rate_limit_denial_after_failed_requests_does_not_complete_onboarding(): void
    {
        config()->set('ukapi.free_plan.burst_requests_per_second', 1);
        $user = User::factory()->create();
        [, $token] = $this->createKey($user);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/vat/calculate?amount=invalid&rate=20', $headers)->assertUnprocessable();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)
            ->assertTooManyRequests()->assertJsonPath('error.code', 'rate_limit_exceeded');
        $this->assertDashboard($user, null, 1);
    }

    public function test_upstream_failure_does_not_complete_onboarding(): void
    {
        $user = User::factory()->create();
        [, $token] = $this->createKey($user);
        Http::fake(['https://api.postcodes.io/*' => Http::response([], 503)]);

        $this->getJson('/v1/postcodes/BL2%206XX', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(503);
        $this->assertDashboard($user, null, 1);
    }

    public function test_api_success_belongs_to_the_key_owner_not_the_web_session_user(): void
    {
        $owner = User::factory()->create();
        [, $token] = $this->createKey($owner);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->assertNotNull($owner->fresh()->first_api_success_at);
        $this->assertNull($otherUser->fresh()->first_api_success_at);
    }

    #[DataProvider('responseStatuses')]
    public function test_tracker_only_marks_authenticated_2xx_responses(int $status, bool $successful): void
    {
        $user = User::factory()->create();
        [$key] = $this->createKey($user);
        $request = Request::create('/v1/example');
        $request->attributes->set('ukapi.api_key', $key);
        $response = new Response('', $status);

        $this->assertSame($response, app(TrackFirstApiSuccess::class)->handle($request, fn () => $response));
        $this->assertSame($successful, $user->fresh()->first_api_success_at !== null);
    }

    public static function responseStatuses(): array
    {
        return [[200, true], [201, true], [204, true], [299, true], [302, false], [400, false], [401, false], [403, false], [404, false], [422, false], [429, false], [500, false]];
    }

    public function test_success_without_api_authentication_is_not_tracked(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        app(TrackFirstApiSuccess::class)->handle($request, fn () => new Response('', 200));

        $this->assertNull($user->fresh()->first_api_success_at);
    }

    private function assertDashboard(User $user, ?string $firstSuccess, int $monthlyRequests): void
    {
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('summary.first_api_success_at', $firstSuccess)
            ->where('summary.monthly_requests', $monthlyRequests));
    }

    /** @return array{ApiKey, string} */
    private function createKey(User $user, string $environment = 'test'): array
    {
        $response = $this->actingAs($user->fresh())->post(route('api-keys.store'), [
            'name' => 'Onboarding '.$environment,
            'environment' => $environment,
        ])->assertRedirect(route('api-keys.index'));

        return [
            $user->apiKeys()->latest('id')->firstOrFail(),
            $response->getSession()->get('api_key_created.token'),
        ];
    }
}

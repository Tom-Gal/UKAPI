<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Notifications\UsageLimitNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_key_can_calculate_vat_with_the_standard_envelope(): void
    {
        [, $token] = $this->createApiKey();

        $response = $this->getJson('/v1/vat/calculate?amount=99.99&rate=20', [
            'Authorization' => "Bearer {$token}",
            'X-Request-Id' => 'req_edge-123456',
        ]);

        $response
            ->assertOk()
            ->assertHeader('X-Request-Id', 'req_edge-123456')
            ->assertHeader('RateLimit-Limit', '5')
            ->assertHeader('X-Quota-Limit', '5000')
            ->assertJsonPath('data.amount', '99.99')
            ->assertJsonPath('data.rate_percent', '20.00')
            ->assertJsonPath('data.vat_amount', '20.00')
            ->assertJsonPath('data.total_amount', '119.99')
            ->assertJsonPath('data.currency', 'GBP')
            ->assertJsonPath('meta.request_id', 'req_edge-123456')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'ukapi');
    }

    public function test_a_valid_key_can_remove_vat_without_using_floating_point_math(): void
    {
        [, $token] = $this->createApiKey();

        $this->getJson('/v1/vat/remove?amount=120.00&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.gross_amount', '120.00')
            ->assertJsonPath('data.net_amount', '100.00')
            ->assertJsonPath('data.vat_amount', '20.00')
            ->assertJsonPath('data.currency', 'GBP');
    }

    public function test_an_invalid_or_revoked_key_returns_the_standard_error_envelope(): void
    {
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => 'Bearer uk_test_k_abcdefghij.invalidsecretvaluethatislongenough',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'invalid_api_key')
            ->assertJsonPath('error.status', 401)
            ->assertHeader('X-Request-Id');

        [$apiKey, $token] = $this->createApiKey(status: 'revoked');

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'api_key_revoked');

        $this->assertSame('revoked', $apiKey->status);
    }

    public function test_invalid_parameters_use_the_standard_error_envelope_without_consuming_quota(): void
    {
        [$apiKey, $token] = $this->createApiKey();

        $this->getJson('/v1/vat/calculate?amount=invalid&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter')
            ->assertJsonPath('error.status', 422);

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertHeader('X-Quota-Remaining', '4999');

        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }

    public function test_the_burst_rate_limit_uses_a_per_key_counter(): void
    {
        [, $token] = $this->createApiKey();

        $this->travelTo('2026-09-23 12:00:00');

        foreach (range(1, 5) as $attempt) {
            $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
                'Authorization' => "Bearer {$token}",
            ])->assertOk();
        }

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertTooManyRequests()
            ->assertHeader('RateLimit-Limit', '5')
            ->assertHeader('RateLimit-Remaining', '0')
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.code', 'rate_limit_exceeded');

        $this->travelBack();
    }

    public function test_monthly_quota_is_enforced_at_the_account_level(): void
    {
        config()->set('ukapi.free_plan.monthly_request_quota', 1);
        $user = User::factory()->create();
        [, $firstToken] = $this->createApiKey($user);
        [, $secondToken] = $this->createApiKey($user);

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$firstToken}",
        ])->assertOk();

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$secondToken}",
        ])
            ->assertTooManyRequests()
            ->assertHeader('X-Quota-Limit', '1')
            ->assertHeader('X-Quota-Remaining', '0')
            ->assertJsonPath('error.code', 'quota_exceeded');
    }

    public function test_usage_alerts_are_sent_once_when_a_threshold_is_reached(): void
    {
        Notification::fake();
        config()->set('ukapi.free_plan.monthly_request_quota', 10);
        config()->set('ukapi.usage_notification_thresholds', '10');
        $user = User::factory()->create();
        [, $token] = $this->createApiKey($user);

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $this->getJson('/v1/vat/remove?amount=120&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        Notification::assertSentTo($user, UsageLimitNotification::class, 1);
    }

    public function test_the_openapi_contract_is_available_without_an_api_key(): void
    {
        $this->get('/openapi/v1.yaml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8')
            ->assertHeader('X-Request-Id');
    }

    /**
     * @return array{ApiKey, string}
     */
    private function createApiKey(?User $user = null, string $status = 'active'): array
    {
        $user ??= User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'API test key',
            'environment' => 'test',
            'status' => $status,
            'revoked_at' => $status === 'revoked' ? now() : null,
        ]);

        return [$apiKey, "uk_test_{$apiKey->public_id}.{$secret}"];
    }
}

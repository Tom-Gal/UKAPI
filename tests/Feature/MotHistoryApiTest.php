<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MotHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('ukapi.mot_history.api_key', 'mot-api-key');
        config()->set('ukapi.mot_history.access_token', 'mot-access-token');
    }

    public function test_registration_lookup_normalises_the_contract_and_caches_the_result(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://history.mot.api.gov.uk/v1/trade/vehicles/registration/AB12CDE' => Http::response($this->fixture(), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];
        $this->getJson('/v1/mot/registration/ab12%20cde', $headers)
            ->assertOk()
            ->assertJsonPath('data.registration', 'AB12CDE')
            ->assertJsonPath('data.motTests.0.motTestNumber', '123456789012')
            ->assertJsonPath('data.motTests.0.registrationAtTimeOfTest', 'AB12CDE')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'mot_history')
            ->assertJsonPath('meta.source_updated_at', null)
            ->assertJsonPath('meta.coverage', 'Great Britain and Northern Ireland')
            ->assertJsonMissingPath('data.uncontractedProviderField');

        $this->getJson('/v1/mot/registration/AB12CDE', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => $request->header('Authorization')[0] === 'Bearer mot-access-token'
            && $request->header('X-API-Key')[0] === 'mot-api-key');
    }

    public function test_vin_lookup_rejects_invalid_vins_before_calling_dvsa(): void
    {
        $token = $this->createApiKey();
        Http::fake();

        $this->getJson('/v1/mot/vin/INVALID', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');

        Http::assertNothingSent();
    }

    public function test_not_found_responses_are_negatively_cached(): void
    {
        $token = $this->createApiKey();
        Http::fake(['*' => Http::response(['code' => 'MOTH-NF-01'], 404)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/mot/registration/AB12CDE', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'mot_vehicle_not_found');
        $this->getJson('/v1/mot/registration/AB12CDE', $headers)
            ->assertNotFound()->assertJsonPath('error.code', 'mot_vehicle_not_found');

        Http::assertSentCount(1);
    }

    #[DataProvider('providerFailureResponses')]
    public function test_provider_failures_use_controlled_errors(int $status, string $code): void
    {
        $token = $this->createApiKey();
        Http::fake(['*' => Http::response(['private' => 'provider detail'], $status)]);

        $response = $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => "Bearer {$token}"])
            ->assertJsonPath('error.code', $code);

        $this->assertStringNotContainsString('provider detail', $response->getContent());
    }

    public static function providerFailureResponses(): iterable
    {
        yield 'rejected API credential' => [401, 'upstream_authentication_failed'];
        yield 'forbidden API credential' => [403, 'upstream_authentication_failed'];
        yield 'provider throttle' => [429, 'upstream_unavailable'];
        yield 'provider outage' => [503, 'upstream_unavailable'];
        yield 'unexpected client response' => [400, 'upstream_invalid_response'];
    }

    #[DataProvider('invalidPayloads')]
    public function test_schema_drift_is_rejected(array $payload): void
    {
        $token = $this->createApiKey();
        Http::fake(['*' => Http::response($payload, 200)]);

        $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');
    }

    public static function invalidPayloads(): iterable
    {
        yield 'list response' => [['unexpected']];
        yield 'missing recall status' => [['registration' => 'AB12CDE', 'motTests' => []]];
        yield 'tests not a list' => [['registration' => 'AB12CDE', 'hasOutstandingRecall' => 'No', 'motTests' => 'invalid']];
        yield 'test not an object' => [['registration' => 'AB12CDE', 'hasOutstandingRecall' => 'No', 'motTests' => ['invalid']]];
        yield 'defects not a list' => [[
            'registration' => 'AB12CDE',
            'hasOutstandingRecall' => 'No',
            'motTests' => [[
                'testResult' => 'PASSED',
                'odometerResultType' => 'READ',
                'dataSource' => 'DVSA',
                'defects' => 'invalid',
            ]],
        ]];
    }

    public function test_corrupt_cached_data_is_evicted_and_reported(): void
    {
        $token = $this->createApiKey();
        $key = 'ukapi:v1:mot:registration:AB12CDE';
        Cache::put($key, ['state' => 'found', 'data' => ['registration' => 'AB12CDE']]);
        Http::fake();

        $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');

        $this->assertFalse(Cache::has($key));
        Http::assertNothingSent();
    }

    public function test_client_credentials_token_is_acquired_and_cached(): void
    {
        config()->set([
            'ukapi.mot_history.access_token' => null,
            'ukapi.mot_history.client_id' => 'client-id',
            'ukapi.mot_history.client_secret' => 'client-secret',
            'ukapi.mot_history.token_url' => 'https://login.example.test/oauth2/token',
            'ukapi.mot_history.scope' => 'https://tapi.dvsa.gov.uk/.default',
        ]);
        $token = $this->createApiKey();
        Http::fake([
            'https://login.example.test/oauth2/token' => Http::response([
                'access_token' => 'acquired-token',
                'expires_in' => 3600,
            ], 200),
            'https://history.mot.api.gov.uk/*' => Http::response($this->fixture(), 200),
        ]);

        $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => "Bearer {$token}"])->assertOk();

        Http::assertSent(fn (HttpClientRequest $request): bool => $request->url() === 'https://login.example.test/oauth2/token'
            && $request['client_secret'] === 'client-secret');
        $this->assertSame(
            'acquired-token',
            Cache::get('ukapi:provider:mot_history:access_token:'.hash('sha256', 'client-id')),
        );
    }

    public function test_missing_credentials_are_a_controlled_configuration_failure(): void
    {
        config()->set([
            'ukapi.mot_history.api_key' => null,
            'ukapi.mot_history.access_token' => null,
            'ukapi.mot_history.client_id' => null,
            'ukapi.mot_history.client_secret' => null,
        ]);
        Http::fake();

        $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => 'Bearer '.$this->createApiKey()])
            ->assertServiceUnavailable()
            ->assertJsonPath('error.code', 'upstream_unavailable');

        Http::assertNothingSent();
    }

    public function test_customer_requests_honour_the_shared_mot_provider_budget(): void
    {
        $token = $this->createApiKey();
        config()->set([
            'ukapi.mot_history.provider_request_limit' => 1,
            'ukapi.mot_history.provider_limit_window_seconds' => 60,
        ]);
        $window = (int) config('ukapi.mot_history.provider_limit_window_seconds');
        RateLimiter::hit('ukapi:provider:mot_history:'.intdiv(now()->getTimestamp(), $window), $window);
        Http::fake();

        $this->getJson('/v1/mot/registration/AB12CDE', ['Authorization' => "Bearer {$token}"])
            ->assertServiceUnavailable()
            ->assertJsonPath('error.code', 'upstream_unavailable');

        Http::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        $payload = json_decode((string) file_get_contents(base_path('tests/Fixtures/MotHistory/vehicle.json')), true);
        $this->assertIsArray($payload);

        return $payload;
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'MOT test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }
}

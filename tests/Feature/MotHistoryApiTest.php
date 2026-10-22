<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    public function test_registration_lookup_uses_dvsa_authentication_and_the_standard_envelope(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://history.mot.api.gov.uk/v1/trade/vehicles/registration/AB12CDE' => Http::response([
                'registration' => 'AB12CDE',
                'motTests' => [],
            ], 200),
        ]);

        $this->getJson('/v1/mot/registration/ab12%20cde', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.registration', 'AB12CDE')
            ->assertJsonPath('meta.source', 'mot_history')
            ->assertJsonPath('meta.coverage', 'GB and Northern Ireland');

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

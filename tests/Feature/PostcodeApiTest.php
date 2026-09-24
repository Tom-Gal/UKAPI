<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostcodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_normalises_postcodes_and_serves_a_second_request_from_cache(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->fixture('lookup-success'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/postcodes/bl2%206xx', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.local_authority.name', 'Bolton')
            ->assertJsonPath('data.local_authority.code', 'E08000001')
            ->assertJsonPath('data.statistical_geography.code', 'TLD36')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'postcodes_io');

        $this->getJson('/v1/postcodes/BL26XX', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => $request->url() === 'https://api.postcodes.io/postcodes/BL26XX');
    }

    public function test_validation_returns_false_for_a_well_formed_postcode_that_has_no_provider_record(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->fixture('not-found'), 404),
        ]);

        $this->getJson('/v1/postcodes/bl2%206xx/validate', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('meta.cached', false);

        Http::assertSentCount(1);
    }

    public function test_invalid_postcodes_are_rejected_before_calling_the_provider(): void
    {
        $token = $this->createApiKey();
        Http::fake();

        $this->getJson('/v1/postcodes/not-a-postcode', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_postcode')
            ->assertJsonPath('error.status', 422);

        Http::assertNothingSent();
    }

    public function test_nearby_endpoint_forwards_bounded_query_parameters_and_caches_results(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX/nearest*' => Http::response($this->fixture('nearest-success'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/postcodes/BL26XX/nearby?limit=5&radius=500', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonCount(2, 'data.results')
            ->assertJsonPath('data.results.1.postcode', 'BL2 6XY')
            ->assertJsonPath('data.results.1.distance_metres', 18.6)
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/postcodes/BL2%206XX/nearby?limit=5&radius=500', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'limit=5') && str_contains($request->url(), 'radius=500'));
    }

    public function test_reverse_geocoding_returns_the_nearest_postcode_and_caches_the_result(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes*' => Http::response($this->fixture('nearest-success'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/coordinates/53.5922/-2.4117/postcode', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.distance_metres', 0)
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/coordinates/53.592200/-2.411700/postcode', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'lon=-2.4117') && str_contains($request->url(), 'lat=53.5922'));
    }

    public function test_provider_failures_are_reported_without_exposing_the_raw_response(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response(['status' => 500], 500),
        ]);

        $this->getJson('/v1/postcodes/BL26XX', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'upstream_unavailable')
            ->assertJsonMissingPath('error.details');
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Postcode test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/PostcodesIo/{$name}.json"));

        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

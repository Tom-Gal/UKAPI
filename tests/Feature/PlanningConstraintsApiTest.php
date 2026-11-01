<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PlanningConstraintsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_postcode_lookup_resolves_coordinates_and_normalises_constraints(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->fixture('PostcodesIo/lookup-success'), 200),
            'https://www.planning.data.gov.uk/entity.json*' => Http::response($this->fixture('Planning/entities'), 200),
        ]);

        $this->getJson('/v1/planning/constraints?postcode=BL2%206XX&limit=25', [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.0.id', 'conservation-area:44002870')
            ->assertJsonPath('data.0.dataset', 'conservation-area')
            ->assertJsonPath('data.0.location.latitude', 53.5922)
            ->assertJsonPath('meta.coverage', 'England')
            ->assertJsonPath('meta.dataset_sources.0.dataset', 'conservation-area')
            ->assertJsonPath('meta.dataset_sources.0.licence', 'Open Government Licence v3.0')
            ->assertJsonPath('meta.dataset_sources.0.attribution', '© Historic England 2026. Contains Ordnance Survey data © Crown copyright and database right 2026.')
            ->assertJsonPath('meta.dataset_sources.0.limitation', fn (mixed $value): bool => str_contains((string) $value, 'duplicate'))
            ->assertJsonPath('meta.disclaimer', fn (mixed $value): bool => str_contains((string) $value, 'not an official property search'));

        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'latitude=53.5922')
            && str_contains($request->url(), 'longitude=-2.4117')
            && str_contains($request->url(), 'dataset=conservation-area')
            && str_contains($request->url(), 'limit=25'));
    }

    public function test_coordinate_lookup_does_not_require_postcode_resolution(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://www.planning.data.gov.uk/entity.json*' => Http::response(['entities' => []], 200),
        ]);

        $this->getJson('/v1/planning/constraints?lat=53.5922&lng=-2.4117', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk()->assertJsonPath('data', []);

        Http::assertNotSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'api.postcodes.io'));
    }

    public function test_invalid_input_and_schema_drift_are_safe(): void
    {
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/planning/constraints', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
        $this->getJson('/v1/planning/constraints?lat=53.5922', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
        $this->getJson('/v1/planning/constraints?postcode=BL2%206XX&lat=53.5922&lng=-2.4117', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');

        Http::fake(['https://www.planning.data.gov.uk/entity.json*' => Http::response(['entities' => [['unexpected' => true]]], 200)]);
        $this->getJson('/v1/planning/constraints?lat=53.5922&lng=-2.4117', $headers)
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');
    }

    public function test_planning_constraints_can_serve_a_validated_stale_collection(): void
    {
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];
        $path = '/v1/planning/constraints?lat=53.5922&lng=-2.4117';
        Http::fake(['https://www.planning.data.gov.uk/entity.json*' => Http::response($this->fixture('Planning/entities'), 200)]);

        $this->getJson($path, $headers)->assertOk()->assertJsonPath('meta.stale', false);
        $this->travel(25)->hours();
        Http::fake(['https://www.planning.data.gov.uk/entity.json*' => Http::failedConnection('private planning detail')]);

        $response = $this->getJson($path, $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true)
            ->assertJsonPath('meta.stale', true);
        $this->assertStringNotContainsString('private planning detail', $response->getContent());
    }

    #[DataProvider('providerFailureResponses')]
    public function test_provider_failures_use_controlled_errors(int $status, string $code): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://www.planning.data.gov.uk/entity.json*' => Http::response(['private' => 'provider detail'], $status)]);

        $response = $this->getJson('/v1/planning/constraints?lat=53.5922&lng=-2.4117', [
            'Authorization' => "Bearer {$token}",
        ])->assertJsonPath('error.code', $code);

        $this->assertStringNotContainsString('provider detail', $response->getContent());
    }

    public static function providerFailureResponses(): iterable
    {
        yield 'provider address throttle' => [403, 'upstream_unavailable'];
        yield 'provider resource throttle' => [429, 'upstream_unavailable'];
        yield 'provider outage' => [503, 'upstream_unavailable'];
        yield 'unexpected client response' => [400, 'upstream_invalid_response'];
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Planning test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/{$name}.json"));
        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

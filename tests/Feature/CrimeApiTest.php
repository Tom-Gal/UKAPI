<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrimeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_crime_endpoints_require_a_ukapi_bearer_key(): void
    {
        $this->getJson('/v1/crime/categories')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'invalid_api_key');
    }

    public function test_nearby_crime_normalises_a_postcode_and_serves_a_second_request_from_cache(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->postcodeFixture(), 200),
            'https://data.police.uk/api/crimes-street/all-crime*' => Http::response($this->fixture('crimes-street'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/crime/nearby?postcode=bl2%206xx&month=2026-07', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonCount(2, 'data.crimes')
            ->assertJsonPath('data.crimes.0.category', 'anti-social-behaviour')
            ->assertJsonPath('data.crimes.0.location.approximate', true)
            ->assertJsonPath('data.crimes.1.outcome.category', 'Under investigation')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'police_uk')
            ->assertJsonPath('meta.requested_month', '2026-07')
            ->assertJsonPath('meta.data_is_approximate', true);

        $this->getJson('/v1/crime/nearby?postcode=BL26XX&month=2026-07', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(2);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'lat=53.5922')
            && str_contains($request->url(), 'lng=-2.4117')
            && str_contains($request->url(), 'date=2026-07'));
    }

    public function test_crime_summary_returns_stable_category_counts(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->postcodeFixture(), 200),
            'https://data.police.uk/api/crimes-street/all-crime*' => Http::response($this->fixture('crimes-street'), 200),
        ]);

        $this->getJson('/v1/crime/summary?postcode=BL2%206XX&month=2026-07', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.month', '2026-07')
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.by_category.0.category', 'anti-social-behaviour')
            ->assertJsonPath('data.by_category.0.count', 1)
            ->assertJsonPath('data.by_category.1.category', 'violent-crime')
            ->assertJsonPath('data.by_category.1.count', 1);
    }

    public function test_crime_categories_are_cached_without_a_postcode_lookup(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://data.police.uk/api/crime-categories*' => Http::response($this->fixture('crime-categories'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/crime/categories?month=2026-07', $headers)
            ->assertOk()
            ->assertJsonPath('data.categories.0.code', 'all-crime')
            ->assertJsonPath('data.categories.1.name', 'Burglary')
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/crime/categories?month=2026-07', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
    }

    public function test_invalid_months_are_rejected_before_any_provider_request(): void
    {
        $token = $this->createApiKey();
        Http::fake();

        $this->getJson('/v1/crime/nearby?postcode=BL2%206XX&month=2026-7', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter')
            ->assertJsonPath('error.details.fields.month.0', 'The month field must match the format Y-m.');

        Http::assertNothingSent();
    }

    public function test_future_months_are_rejected_before_any_provider_request(): void
    {
        $token = $this->createApiKey();
        Http::fake();
        $futureMonth = now()->addMonth()->format('Y-m');

        $this->getJson("/v1/crime/categories?month={$futureMonth}", ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter')
            ->assertJsonPath('error.details.fields.month.0', 'Month must use the YYYY-MM format and cannot be in the future.');

        Http::assertNothingSent();
    }

    public function test_upstream_police_failures_do_not_expose_provider_payloads(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->postcodeFixture(), 200),
            'https://data.police.uk/api/crimes-street/all-crime*' => Http::response(['error' => 'temporary provider detail'], 503),
        ]);

        $this->getJson('/v1/crime/nearby?postcode=BL2%206XX&month=2026-07', ['Authorization' => "Bearer {$token}"])
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
            'name' => 'Crime test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    /** @return array<string, mixed> */
    private function postcodeFixture(): array
    {
        $contents = file_get_contents(base_path('tests/Fixtures/PostcodesIo/lookup-success.json'));

        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }

    /** @return list<array<string, mixed>> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/PoliceUk/{$name}.json"));

        self::assertIsString($contents);

        /** @var list<array<string, mixed>> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

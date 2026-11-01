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

final class FoodHygieneApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishment_lookup_maps_fsa_data_and_sends_the_version_header(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.ratings.food.gov.uk/Establishments/123456' => Http::response($this->fixture('Food/establishment'), 200),
        ]);

        $this->getJson('/v1/food-establishments/123456', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.id', 123456)
            ->assertJsonPath('data.scheme_type', 'FHRS')
            ->assertJsonPath('data.rating.value', '5')
            ->assertJsonPath('data.location.latitude', 53.5922)
            ->assertJsonPath('meta.source', 'fsa_food_hygiene');

        Http::assertSent(fn (HttpClientRequest $request): bool => $request->hasHeader('x-api-version', '2'));
    }

    public function test_search_preserves_fhrs_and_fhis_rating_semantics_and_paginates(): void
    {
        $token = $this->createApiKey();
        $fhrs = $this->fixture('Food/establishments');
        $fhrs['establishments'][0]['SchemeType'] = 'FHIS';
        $fhrs['establishments'][0]['RatingKey'] = 'Pass';
        $fhrs['establishments'][0]['RatingValue'] = 'Pass';
        $fhrs['establishments'][0]['scores'] = [
            'Hygiene' => null,
            'Structural' => null,
            'ConfidenceInManagement' => null,
        ];
        Http::fake(['https://api.ratings.food.gov.uk/Establishments*' => Http::response($fhrs, 200)]);

        $this->getJson('/v1/food-establishments/search?name=Example%20Kitchen&scheme=FHIS&rating=Pass&page=2&per_page=10', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.scheme_type', 'FHIS')
            ->assertJsonPath('data.0.rating.key', 'Pass')
            ->assertJsonPath('data.0.scores.hygiene', null)
            ->assertJsonPath('meta.pagination.page', 2)
            ->assertJsonPath('meta.pagination.per_page', 10);

        Http::assertSent(fn (HttpClientRequest $request): bool => $request['schemeTypeKey'] === 'FHIS'
            && $request['ratingKey'] === 'Pass'
            && $request['pageNumber'] === 2
            && $request['pageSize'] === 10);
    }

    public function test_nearby_search_resolves_coordinates_through_the_existing_geography_boundary(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->fixture('PostcodesIo/lookup-success'), 200),
            'https://api.ratings.food.gov.uk/Establishments*' => Http::response($this->fixture('Food/establishments'), 200),
        ]);

        $this->getJson('/v1/food-establishments/nearby?postcode=BL2%206XX&radius=5', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Example Kitchen')
            ->assertJsonPath('data.0.location.distance_km', 0.322)
            ->assertJsonPath('meta.coverage', 'UK, participating local authorities');

        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'latitude=53.5922')
            && str_contains($request->url(), 'longitude=-2.4117')
            && str_contains($request->url(), 'maxDistanceLimit='));
    }

    public function test_invalid_and_not_found_establishment_requests_are_safe(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.ratings.food.gov.uk/Establishments/999999' => Http::response([], 404)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/food-establishments/nope', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_food_establishment_id');
        $this->getJson('/v1/food-establishments/999999', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'food_establishment_not_found');
        $this->getJson('/v1/food-establishments/search', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
    }

    public function test_search_can_serve_a_validated_stale_collection_during_an_upstream_failure(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.ratings.food.gov.uk/Establishments*' => Http::response($this->fixture('Food/establishments'), 200)]);
        $headers = ['Authorization' => "Bearer {$token}"];
        $path = '/v1/food-establishments/search?name=Example%20Kitchen';

        $this->getJson($path, $headers)->assertOk()->assertJsonPath('meta.stale', false);
        $this->travel(7)->hours();
        Http::fake(['https://api.ratings.food.gov.uk/Establishments*' => Http::failedConnection('private provider detail')]);

        $response = $this->getJson($path, $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true)
            ->assertJsonPath('meta.stale', true);
        $this->assertStringNotContainsString('private provider detail', $response->getContent());
    }

    #[DataProvider('providerFailureResponses')]
    public function test_provider_failures_use_controlled_errors(int $status, string $code): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.ratings.food.gov.uk/Establishments/123456' => Http::response(['private' => 'provider detail'], $status)]);

        $response = $this->getJson('/v1/food-establishments/123456', ['Authorization' => "Bearer {$token}"])
            ->assertJsonPath('error.code', $code);

        $this->assertStringNotContainsString('provider detail', $response->getContent());
    }

    public static function providerFailureResponses(): iterable
    {
        yield 'rejected API credential' => [401, 'upstream_authentication_failed'];
        yield 'provider address throttle' => [403, 'upstream_unavailable'];
        yield 'provider resource throttle' => [429, 'upstream_unavailable'];
        yield 'provider outage' => [503, 'upstream_unavailable'];
        yield 'unexpected client response' => [400, 'upstream_invalid_response'];
    }

    public function test_empty_search_results_are_valid_and_schema_drift_is_rejected(): void
    {
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];
        Http::fake(['https://api.ratings.food.gov.uk/Establishments*' => Http::sequence()
            ->push([
                'establishments' => [],
                'meta' => ['totalCount' => 0, 'extractDate' => '2026-10-31T02:30:00+00:00'],
            ], 200)
            ->push(['unexpected' => true], 200)]);

        $this->getJson('/v1/food-establishments/search?name=No%20Such%20Kitchen', $headers)
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.pagination.total', 0);

        $this->getJson('/v1/food-establishments/search?name=Schema%20Drift', $headers)
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');
    }

    public function test_missing_optional_fsa_fields_are_normalised_to_null(): void
    {
        $token = $this->createApiKey();
        $payload = $this->fixture('Food/establishment');
        unset(
            $payload['BusinessType'],
            $payload['BusinessTypeID'],
            $payload['AddressLine2'],
            $payload['Phone'],
            $payload['LocalAuthorityWebSite'],
            $payload['RightToReply'],
            $payload['geocode'],
            $payload['scores'],
        );
        Http::fake(['https://api.ratings.food.gov.uk/Establishments/123456' => Http::response($payload, 200)]);

        $this->getJson('/v1/food-establishments/123456', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.business_type', null)
            ->assertJsonPath('data.location.latitude', null)
            ->assertJsonPath('data.scores.hygiene', null);
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Food hygiene test key',
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
        return json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Snapshots\SnapshotStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FloodApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_flood_endpoints_require_a_ukapi_bearer_key(): void
    {
        $this->getJson('/v1/flood/warnings')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'invalid_api_key');
    }

    public function test_warnings_are_normalised_and_cached_for_five_minutes(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://environment.data.gov.uk/flood-monitoring/id/floods' => Http::response($this->fixture('flood-warnings'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/flood/warnings', $headers)
            ->assertOk()
            ->assertJsonPath('data.warnings.0.id', '061WAFSF3A')
            ->assertJsonPath('data.warnings.0.area.river_or_sea', 'River Tone')
            ->assertJsonPath('data.warnings.0.severity_level', 'Flood Warning')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'environment_agency_flood_monitoring')
            ->assertJsonPath('meta.coverage', 'England')
            ->assertJsonPath('meta.source_updated_at', '2026-09-25T10:15:00+00:00');

        $this->getJson('/v1/flood/warnings', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
    }

    public function test_nearby_warnings_and_stations_use_normalised_postcode_coordinates(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->postcodeFixture(), 200),
            'https://environment.data.gov.uk/flood-monitoring/id/floods*' => Http::response($this->fixture('flood-warnings'), 200),
            'https://environment.data.gov.uk/flood-monitoring/id/stations*' => Http::response($this->fixture('stations'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/flood/nearby?postcode=bl2%206xx', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.nearby_distance_kilometres', 10)
            ->assertJsonPath('data.warnings.0.id', '061WAFSF3A')
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/flood/stations/nearby?postcode=BL26XX', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.nearby_distance_kilometres', 10)
            ->assertJsonPath('data.stations.0.id', '5380TH')
            ->assertJsonPath('data.stations.0.measures.0.parameter_name', 'Water Level')
            ->assertJsonPath('data.stations.0.status', 'statusActive');

        Http::assertSentCount(3);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'lat=53.5922')
            && str_contains($request->url(), 'long=-2.4117')
            && str_contains($request->url(), 'dist=10'));
    }

    public function test_station_readings_are_capped_and_cached(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://environment.data.gov.uk/flood-monitoring/id/stations/5380TH/readings*' => Http::response($this->fixture('readings'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/flood/stations/5380TH/readings', $headers)
            ->assertOk()
            ->assertJsonPath('data.station_id', '5380TH')
            ->assertJsonPath('data.readings.0.value', 0.027)
            ->assertJsonPath('data.readings.0.recorded_at', '2026-08-27T00:00:00+00:00')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source_updated_at', '2026-08-27T00:00:00+00:00');

        $this->getJson('/v1/flood/stations/5380TH/readings', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), '_limit=100'));
    }

    public function test_pro_can_read_flood_reading_changes_between_snapshots(): void
    {
        $token = $this->createProApiKey();
        $old = app(SnapshotStore::class)->capture(
            'environment_agency.flood.readings',
            '5380TH',
            'environment_agency_flood_monitoring',
            [
                [
                    'measure_id' => 'http://environment.data.gov.uk/flood-monitoring/id/measures/5380TH-level-stage-i-15_min-mASD',
                    'recorded_at' => '2026-08-27T00:00:00+00:00',
                    'value' => 0.010,
                ],
                [
                    'measure_id' => 'http://environment.data.gov.uk/flood-monitoring/id/measures/5380TH-level-stage-i-15_min-mASD',
                    'recorded_at' => '2026-08-26T00:00:00+00:00',
                    'value' => 0.020,
                ],
            ],
        );
        Http::fake([
            'https://environment.data.gov.uk/flood-monitoring/id/stations/5380TH/readings*' => Http::response($this->fixture('readings'), 200),
        ]);

        $this->getJson("/v1/flood/stations/5380TH/changes?since={$old->version}", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.station_id', '5380TH')
            ->assertJsonPath('data.since', $old->version)
            ->assertJsonCount(1, 'data.removed')
            ->assertJsonCount(1, 'data.changed')
            ->assertJsonPath('meta.source', 'environment_agency_flood_monitoring')
            ->assertJsonPath('meta.to_snapshot.checksum', fn ($checksum): bool => is_string($checksum));

        Http::assertSentCount(1);
        $this->assertDatabaseCount('dataset_snapshots', 2);
    }

    public function test_explicit_reading_versions_are_compared_without_fetching_or_changing_the_active_snapshot(): void
    {
        $token = $this->createProApiKey();
        $store = app(SnapshotStore::class);
        $record = ['measure_id' => 'measure-a', 'recorded_at' => '2026-08-27T00:00:00+00:00', 'value' => 1];
        $from = $store->capture('environment_agency.flood.readings', '5380TH', 'test', [$record]);
        $to = $store->capture('environment_agency.flood.readings', '5380TH', 'test', [array_replace($record, ['value' => 2])]);
        $active = $store->capture('environment_agency.flood.readings', '5380TH', 'test', []);
        Http::fake(['*' => Http::response([], 503)]);

        $this->getJson("/v1/flood/stations/5380th/changes?since={$from->version}&until={$to->version}", [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertOk()
            ->assertJsonPath('data.station_id', '5380TH')
            ->assertJsonPath('data.since', $from->version)
            ->assertJsonPath('data.until', $to->version)
            ->assertJsonPath('data.added', [])
            ->assertJsonPath('data.removed', [])
            ->assertJsonCount(1, 'data.changed')
            ->assertJsonPath('data.changed.0.before.value', 1)
            ->assertJsonPath('data.changed.0.after.value', 2)
            ->assertJsonPath('meta.from_snapshot.checksum', $from->checksum)
            ->assertJsonPath('meta.to_snapshot.checksum', $to->checksum);

        Http::assertNothingSent();
        $this->assertDatabaseCount('dataset_snapshots', 3);
        $this->assertSame($active->id, $store->active('environment_agency.flood.readings', '5380TH')?->id);
    }

    #[DataProvider('invalidReadingVersions')]
    public function test_invalid_reading_versions_are_rejected_before_fetching(string $field, string $kind, bool $explicitUntil): void
    {
        $token = $this->createProApiKey();
        $store = app(SnapshotStore::class);
        $valid = $store->capture('environment_agency.flood.readings', '5380TH', 'test', []);
        $invalidVersion = $kind === 'missing'
            ? (string) Str::ulid()
            : $store->capture(
                $kind === 'dataset' ? 'other.dataset' : 'environment_agency.flood.readings',
                $kind === 'station' ? 'OTHER' : '5380TH',
                'test',
                [],
            )->version;
        $since = $field === 'since' ? $invalidVersion : $valid->version;
        $until = $field === 'until' ? $invalidVersion : $valid->version;
        $query = 'since='.$since.($explicitUntil ? '&until='.$until : '');
        Http::fake(['*' => Http::response($this->fixture('readings'), 200)]);

        $this->getJson('/v1/flood/stations/5380TH/changes?'.$query, [
            'Authorization' => "Bearer {$token}",
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter')
            ->assertJsonPath('error.details.fields.'.$field.'.0', 'The requested flood readings snapshot version was not found.');

        Http::assertNothingSent();
        $this->assertDatabaseCount('dataset_snapshots', $kind === 'missing' ? 1 : 2);
        $this->assertSame($valid->id, $store->active('environment_agency.flood.readings', '5380TH')?->id);
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function invalidReadingVersions(): iterable
    {
        foreach (['missing', 'station', 'dataset'] as $kind) {
            yield 'since-'.$kind.'-latest' => ['since', $kind, false];
            yield 'since-'.$kind.'-explicit' => ['since', $kind, true];
            yield 'until-'.$kind => ['until', $kind, true];
        }
    }

    public function test_invalid_station_ids_are_rejected_without_calling_the_provider(): void
    {
        $token = $this->createApiKey();
        Http::fake();

        $this->getJson('/v1/flood/stations/not%20valid/readings', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter')
            ->assertJsonPath('error.details.fields.id.0', 'The station id must contain only letters, numbers, hyphens or underscores.');

        Http::assertNothingSent();
    }

    public function test_unknown_stations_return_a_normalised_not_found_error(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://environment.data.gov.uk/flood-monitoring/id/stations/UNKNOWN/readings*' => Http::response([], 404),
        ]);

        $this->getJson('/v1/flood/stations/unknown/readings', ['Authorization' => "Bearer {$token}"])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'flood_station_not_found')
            ->assertJsonMissingPath('error.details');
    }

    public function test_provider_failures_return_a_safe_error_without_stale_warning_data(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://environment.data.gov.uk/flood-monitoring/id/floods' => Http::response(['message' => 'provider payload'], 503),
        ]);

        $this->getJson('/v1/flood/warnings', ['Authorization' => "Bearer {$token}"])
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
            'name' => 'Flood test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    private function createProApiKey(): string
    {
        config()->set('ukapi.paid_plans.pro.stripe_price', 'price_pro_flood_test');
        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_pro_flood_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_flood_test',
        ]);
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Flood changes test key',
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

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/EnvironmentAgency/{$name}.json"));

        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

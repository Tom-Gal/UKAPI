<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GeographyCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_outcode_lookup_normalises_and_caches_provider_data(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.postcodes.io/outcodes/BL2' => Http::response($this->fixture('PostcodesIo/outcode-success'), 200)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/outcodes/bl2', $headers)
            ->assertOk()
            ->assertJsonPath('data.outcode', 'BL2')
            ->assertJsonPath('data.northings', 411600)
            ->assertJsonPath('meta.cached', false);
        $this->getJson('/v1/outcodes/BL2', $headers)->assertOk()->assertJsonPath('meta.cached', true);
        Http::assertSentCount(1);
    }

    public function test_outcode_invalid_and_missing_records_are_safe_errors(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.postcodes.io/outcodes/ZZ99' => Http::response(['status' => 404], 404)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/outcodes/not-valid', $headers)->assertUnprocessable()->assertJsonPath('error.code', 'invalid_outcode');
        $this->getJson('/v1/outcodes/ZZ99', $headers)->assertNotFound()->assertJsonPath('error.code', 'outcode_not_found');
    }

    public function test_council_lookup_uses_the_ons_snapshot_and_postcode_mapping(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://api.postcodes.io/postcodes/BL26XX' => Http::response($this->fixture('PostcodesIo/lookup-success'), 200)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/councils/E08000001', $headers)
            ->assertOk()
            ->assertJsonPath('data.name', 'Bolton')
            ->assertJsonPath('data.country', 'England')
            ->assertJsonPath('data.reference_version', '2024-12');
        $this->getJson('/v1/councils/postcode/BL2%206XX', $headers)
            ->assertOk()
            ->assertJsonPath('data.postcode', 'BL2 6XX')
            ->assertJsonPath('data.council.code', 'E08000001');
    }

    public function test_bank_holidays_are_cached_and_working_days_are_bounded_and_inclusive(): void
    {
        $token = $this->createApiKey();
        Http::fake(['https://www.gov.uk/bank-holidays.json' => Http::response($this->fixture('GovUk/bank-holidays'), 200)]);
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/bank-holidays/england-and-wales', $headers)
            ->assertOk()
            ->assertJsonPath('data.division', 'england-and-wales')
            ->assertJsonPath('data.events.1.date', '2026-04-03');
        $this->getJson('/v1/bank-holidays', $headers)->assertOk()->assertJsonPath('meta.cached', true);
        $this->getJson('/v1/working-days?from=2026-04-01&to=2026-04-30&division=england-and-wales', $headers)
            ->assertOk()
            ->assertJsonPath('data.calendar_days', 30)
            ->assertJsonPath('data.working_days', 21)
            ->assertJsonPath('data.holidays_excluded', 1);
        $this->getJson('/v1/working-days?from=2026-04-01&to=2027-04-02&division=england-and-wales', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
        Http::assertSentCount(1);
    }

    public function test_calendar_division_and_tax_year_validation_are_documented_errors(): void
    {
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/bank-holidays/england', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
        $this->getJson('/v1/tax-years/current', $headers)
            ->assertOk()
            ->assertJsonPath('data.tax_year', '2026-27')
            ->assertJsonPath('data.starts_on', '2026-04-06')
            ->assertJsonPath('data.ends_on', '2027-04-05');
        $this->getJson('/v1/tax-years/nope', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Geography utility test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/{$name}.json"));
        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

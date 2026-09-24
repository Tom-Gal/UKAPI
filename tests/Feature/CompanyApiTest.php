<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ukapi.companies_house.api_key', 'fixture-key');
    }

    public function test_company_profile_normalises_the_number_authenticates_upstream_and_caches_the_result(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::response($this->fixture('company-profile'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/companies/sc%20012345', $headers)
            ->assertOk()
            ->assertJsonPath('data.company_number', 'SC012345')
            ->assertJsonPath('data.name', 'EXAMPLE TECHNOLOGY LTD')
            ->assertJsonPath('data.registered_office_address.postal_code', 'EH1 1AA')
            ->assertJsonPath('data.accounts.next_due', '2027-01-31')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.stale', false)
            ->assertJsonPath('meta.source', 'companies_house');

        $this->getJson('/v1/companies/SC012345', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(function (HttpClientRequest $request): bool {
            return $request->url() === 'https://api.company-information.service.gov.uk/company/SC012345'
                && $request->header('Authorization')[0] === 'Basic '.base64_encode('fixture-key:');
        });
    }

    public function test_company_profile_preserves_missing_optional_source_fields_as_null_or_empty_values(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/00012345' => Http::response($this->fixture('company-profile-optional-fields'), 200),
        ]);

        $this->getJson('/v1/companies/00012345', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.registered_office_address', null)
            ->assertJsonPath('data.sic_codes', [])
            ->assertJsonPath('data.accounts.next_due', null)
            ->assertJsonPath('data.dissolved_on', '2010-01-01');
    }

    public function test_company_search_normalises_pagination_and_caches_an_empty_result_set(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/search/companies*' => Http::response($this->fixture('company-search-empty'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/companies/search?q=No%20such%20company&page=2&per_page=10', $headers)
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.pagination.page', 2)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 0)
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/companies/search?q=No%20such%20company&page=2&per_page=10', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'items_per_page=10') && str_contains($request->url(), 'start_index=10'));
    }

    public function test_company_search_returns_a_simplified_stable_schema(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/search/companies*' => Http::response($this->fixture('company-search'), 200),
        ]);

        $this->getJson('/v1/companies/search?q=example', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.company_number', '00012345')
            ->assertJsonPath('data.0.name', 'EXAMPLE LIMITED')
            ->assertJsonPath('data.0.address.locality', 'London')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_company_officers_excludes_birth_data_and_uses_public_pagination(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345/officers*' => Http::response($this->fixture('company-officers'), 200),
        ]);

        $this->getJson('/v1/companies/sc012345/officers?page=1&per_page=25', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.0.name', 'SMITH, Alex')
            ->assertJsonPath('data.0.role', 'director')
            ->assertJsonMissingPath('data.0.date_of_birth')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_company_filing_history_uses_public_pagination_caches_metadata_and_does_not_relay_document_links(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345/filing-history*' => Http::response($this->fixture('company-filings'), 200),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/companies/SC012345/filings?page=2&per_page=10', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.transaction_id', 'MzAwMDAwMDAwMGFkaXF6a2N4')
            ->assertJsonPath('data.0.filed_on', '2025-03-31')
            ->assertJsonPath('data.0.pages', 12)
            ->assertJsonPath('data.1.transaction_id', null)
            ->assertJsonMissingPath('data.0.links')
            ->assertJsonMissingPath('data.0.barcode')
            ->assertJsonPath('meta.pagination.total', 42)
            ->assertJsonPath('meta.cached', false);

        $this->getJson('/v1/companies/SC012345/filings?page=2&per_page=10', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => str_contains($request->url(), 'items_per_page=10') && str_contains($request->url(), 'start_index=10'));
    }

    public function test_company_filing_history_uses_controlled_not_found_and_schema_drift_errors(): void
    {
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];

        Http::fake([
            'https://api.company-information.service.gov.uk/company/00012345/filing-history*' => Http::response($this->fixture('not-found'), 404),
        ]);

        $this->getJson('/v1/companies/00012345/filings', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'company_not_found');

        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345/filing-history*' => Http::response($this->fixture('company-filings-invalid'), 200),
        ]);

        $this->getJson('/v1/companies/SC012345/filings', $headers)
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');
    }

    public function test_sic_lookup_and_search_use_the_local_snapshot_and_cache_results(): void
    {
        config()->set('ukapi.sic_reference.snapshot_path', base_path('tests/Fixtures/CompaniesHouse/sic-reference.json'));
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/sic/62%20012', $headers)
            ->assertOk()
            ->assertJsonPath('data.code', '62012')
            ->assertJsonPath('data.description', 'Business and domestic software development')
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('meta.source', 'companies_house_sic_2007')
            ->assertJsonPath('meta.reference_version', 'companies_house_condensed_sic_2007');

        $this->getJson('/v1/sic/62012', $headers)
            ->assertOk()
            ->assertJsonPath('meta.cached', true);

        $this->getJson('/v1/sic/search?q=software&page=1&per_page=1', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.code', '62012')
            ->assertJsonPath('meta.pagination.page', 1)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.cached', false);
    }

    public function test_sic_validation_not_found_and_snapshot_failures_use_public_errors(): void
    {
        config()->set('ukapi.sic_reference.snapshot_path', base_path('tests/Fixtures/CompaniesHouse/sic-reference.json'));
        $token = $this->createApiKey();
        $headers = ['Authorization' => "Bearer {$token}"];

        $this->getJson('/v1/sic/6201', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_sic_code');

        $this->getJson('/v1/sic/00000', $headers)
            ->assertNotFound()
            ->assertJsonPath('error.code', 'sic_not_found');

        $this->getJson('/v1/sic/search?q=%20', $headers)
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_parameter');

        config()->set('ukapi.sic_reference.snapshot_path', base_path('tests/Fixtures/CompaniesHouse/missing-sic-reference.json'));
        config()->set('ukapi.sic_reference.seed_snapshot_path', base_path('tests/Fixtures/CompaniesHouse/missing-sic-reference.json'));

        $this->getJson('/v1/sic/62012', $headers)
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'sic_reference_unavailable');
    }

    public function test_invalid_company_numbers_do_not_reach_the_provider(): void
    {
        $token = $this->createApiKey();
        Http::fake();

        $this->getJson('/v1/companies/invalid-number!', ['Authorization' => "Bearer {$token}"])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'invalid_company_number');

        Http::assertNothingSent();
    }

    public function test_not_found_and_provider_failures_use_the_public_error_contract(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/00012345' => Http::response($this->fixture('not-found'), 404),
        ]);

        $this->getJson('/v1/companies/00012345', ['Authorization' => "Bearer {$token}"])
            ->assertNotFound()
            ->assertJsonPath('error.code', 'company_not_found');

        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::response($this->fixture('rate-limited'), 429),
        ]);

        $this->getJson('/v1/companies/SC012345', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'upstream_unavailable');

        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::response($this->fixture('server-error'), 500),
        ]);

        $this->getJson('/v1/companies/SC012345', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'upstream_unavailable');
    }

    public function test_schema_drift_is_a_controlled_bad_gateway_response(): void
    {
        $token = $this->createApiKey();
        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::response($this->fixture('invalid-profile'), 200),
        ]);

        $this->getJson('/v1/companies/SC012345', ['Authorization' => "Bearer {$token}"])
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'upstream_invalid_response');
    }

    private function createApiKey(): string
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Company test key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return "uk_test_{$apiKey->public_id}.{$secret}";
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $contents = file_get_contents(base_path("tests/Fixtures/CompaniesHouse/{$name}.json"));

        self::assertIsString($contents);

        /** @var array<string, mixed> $fixture */
        $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        return $fixture;
    }
}

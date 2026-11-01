<?php

namespace Tests\Feature;

use App\Models\DatasetSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompanyProfileSnapshotCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ukapi.companies_house.api_key', 'fixture-key');
    }

    public function test_capture_is_blocked_until_the_companies_house_source_review_is_approved(): void
    {
        Http::fake();

        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'SC012345'])
            ->expectsOutput('Companies House profile snapshots require an approved source review before capture.')
            ->assertFailed();

        Http::assertNothingSent();
        $this->assertDatabaseCount('dataset_snapshots', 0);
    }

    public function test_capture_stores_normalized_profile_and_does_not_duplicate_unchanged_versions(): void
    {
        config()->set('source_reviews.sources.companies_house.review_status', 'reviewed');
        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::response(
                $this->fixture('company-profile'),
                200,
            ),
        ]);

        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'sc 012345'])
            ->expectsOutputToContain('Captured Companies House profile snapshot for SC012345 as ')
            ->assertSuccessful();

        $first = DatasetSnapshot::query()->sole();

        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'SC012345'])
            ->expectsOutputToContain('No change; retained Companies House profile snapshot for SC012345 as ')
            ->assertSuccessful();

        $this->assertDatabaseCount('dataset_snapshots', 1);
        $this->assertSame('companies_house.company_profile', $first->dataset);
        $this->assertSame('SC012345', $first->scope_key);
        $this->assertSame('companies_house', $first->source);
        $this->assertSame(1, $first->record_count);
        $this->assertSame('SC012345', $first->payload[0]['company_number']);
        $this->assertSame('EH1 1AA', $first->payload[0]['registered_office_address']['postal_code']);
        Http::assertSentCount(2);
    }

    public function test_invalid_company_number_fails_without_calling_companies_house(): void
    {
        config()->set('source_reviews.sources.companies_house.review_status', 'reviewed');
        Http::fake();

        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'invalid-number!'])
            ->expectsOutput('Companies House profile snapshot failed (invalid_company_number).')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_changed_profile_creates_a_new_immutable_version(): void
    {
        config()->set('source_reviews.sources.companies_house.review_status', 'reviewed');
        $changed = $this->fixture('company-profile');
        $changed['company_name'] = 'EXAMPLE TECHNOLOGY HOLDINGS LTD';

        Http::fake([
            'https://api.company-information.service.gov.uk/company/SC012345' => Http::sequence()
                ->push($this->fixture('company-profile'), 200)
                ->push($changed, 200),
        ]);

        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'SC012345'])
            ->assertSuccessful();
        $this->artisan('ukapi:companies:profile-snapshot', ['companyNumber' => 'SC012345'])
            ->expectsOutputToContain('Captured Companies House profile snapshot for SC012345 as ')
            ->assertSuccessful();

        $this->assertDatabaseCount('dataset_snapshots', 2);
        $this->assertSame(
            'EXAMPLE TECHNOLOGY HOLDINGS LTD',
            DatasetSnapshot::query()->latest('id')->firstOrFail()->payload[0]['name'],
        );
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

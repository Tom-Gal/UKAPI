<?php

namespace Tests\Feature;

use App\Domain\Companies\SicExport;
use App\Domain\Snapshots\SnapshotStore;
use App\Models\DatasetSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SicExportTest extends TestCase
{
    use RefreshDatabase;

    private ?string $snapshotPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ukapi.sic_reference.snapshot_path', base_path('tests/Fixtures/CompaniesHouse/sic-reference.json'));
        Notification::fake();
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        if ($this->snapshotPath !== null) {
            File::delete($this->snapshotPath);
        }

        parent::tearDown();
    }

    public function test_export_requires_an_api_key(): void
    {
        $this->get('/v1/sic/export')->assertUnauthorized()->assertJsonPath('error.code', 'invalid_api_key');
    }

    public function test_only_scale_can_export_the_full_sic_reference(): void
    {
        foreach (['free', 'hobby', 'pro', 'scale'] as $plan) {
            $response = $this->get('/v1/sic/export', $this->headersFor($plan));

            if ($plan === 'scale') {
                $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
                $this->assertCount(3, $this->parseCsv($response->getContent()));
            } else {
                $response->assertForbidden()
                    ->assertJsonPath('error.code', 'feature_not_available')
                    ->assertJsonPath('error.details.feature', 'bulk_export');
            }
        }
    }

    public function test_csv_is_deterministic_and_identifies_its_immutable_payload_and_source(): void
    {
        $headers = $this->headersFor('scale');
        $first = $this->get('/v1/sic/export', $headers)->assertOk();
        $second = $this->get('/v1/sic/export', $headers)->assertOk();
        $this->assertSame($first->getContent(), $second->getContent());
        $rows = $this->parseCsv($first->getContent());
        $this->assertSame(['62012', '62020', '99999'], array_column($rows, 'code'));
        $snapshot = DatasetSnapshot::query()->sole();
        $first->assertHeader('X-Snapshot-Version', $snapshot->version)
            ->assertHeader('X-Snapshot-Checksum', $snapshot->checksum)
            ->assertHeader('Content-Disposition', 'attachment; filename="sic-'.$snapshot->version.'.csv"')
            ->assertHeader('X-Quota-Remaining', '999999');

        foreach ($rows as $row) {
            $this->assertSame($snapshot->version, $row['snapshot_version']);
            $this->assertSame($snapshot->checksum, $row['snapshot_checksum']);
            $this->assertSame('Companies House condensed SIC 2007', $row['source']);
            $this->assertSame(SicExport::SOURCE_URL, $row['source_url']);
            $this->assertSame('Open Government Licence v3.0', $row['licence']);
            $this->assertSame(SicExport::LICENCE_URL, $row['licence_url']);
            $this->assertSame(SicExport::ATTRIBUTION, $row['attribution']);
        }

        $this->assertSame($snapshot->payload, array_map(
            static fn (array $row): array => ['code' => $row['code'], 'description' => $row['description']],
            $rows,
        ));
        Http::assertNothingSent();
    }

    public function test_csv_round_trips_escaping_and_neutralises_formula_cells(): void
    {
        $descriptions = [
            'Normal, "quoted" text with a backslash \\',
            "Multiple\r\nlines",
            '=SUM(1,2)', '+SUM(1,2)', '-1+2', '@SUM(1,2)',
            "\t=SUM(1,2)", " \t+SUM(1,2)",
        ];
        $records = [];
        foreach ($descriptions as $index => $description) {
            $records[] = ['code' => sprintf('%05d', $index + 1), 'description' => $description];
        }
        $this->writeSnapshot(array_reverse($records));

        $response = $this->get('/v1/sic/export', $this->headersFor('scale'))->assertOk();
        $rows = $this->parseCsv($response->getContent());
        $this->assertSame(array_column($records, 'code'), array_column($rows, 'code'));
        foreach ($descriptions as $index => $description) {
            $this->assertSame(($index < 2 ? '' : "'").trim($description), $rows[$index]['description']);
        }
        $this->assertStringContainsString('""quoted""', $response->getContent());
    }

    public function test_bundled_reference_is_exported_in_full_without_page_size_truncation(): void
    {
        $path = resource_path('reference/sic-2007.json');
        config()->set('ukapi.sic_reference.snapshot_path', $path);
        $expected = array_column(File::json($path)['records'], 'code');
        sort($expected, SORT_STRING);

        $response = $this->get('/v1/sic/export', $this->headersFor('scale'))->assertOk();
        $rows = $this->parseCsv($response->getContent());
        $this->assertGreaterThan(100, count($rows));
        $this->assertSame($expected, array_column($rows, 'code'));
        $this->assertSame('01110', $rows[0]['code']);
    }

    public function test_refresh_changes_the_version_and_checksum_without_mutating_the_previous_export(): void
    {
        $headers = $this->headersFor('scale');
        $old = $this->get('/v1/sic/export', $headers)->assertOk();
        $oldRows = $this->parseCsv($old->getContent());
        $this->writeSnapshot([['code' => '01110', 'description' => 'Updated reference']]);
        $new = $this->get('/v1/sic/export', $headers)->assertOk();
        $newRows = $this->parseCsv($new->getContent());

        $this->assertNotSame($oldRows[0]['snapshot_version'], $newRows[0]['snapshot_version']);
        $this->assertNotSame($oldRows[0]['snapshot_checksum'], $newRows[0]['snapshot_checksum']);
        $this->assertSame('01110', $newRows[0]['code']);
        $this->assertSame('Updated reference', $newRows[0]['description']);
        $stored = app(SnapshotStore::class)->find('companies_house.sic_2007', $oldRows[0]['snapshot_version']);
        $this->assertNotNull($stored);
        $this->assertSame($stored->checksum, $oldRows[0]['snapshot_checksum']);
        $this->assertCount(3, $stored->payload);
        $this->assertSame($oldRows, $this->parseCsv($old->getContent()));
    }

    public function test_scale_exports_consume_quota_and_cannot_exceed_the_hard_limit(): void
    {
        config()->set('ukapi.paid_plans.scale.monthly_request_quota', 1);
        $user = User::factory()->create();
        $headers = $this->headersFor('scale', $user);
        $this->get('/v1/sic/export', $headers)->assertOk()->assertHeader('X-Quota-Remaining', '0');
        $this->get('/v1/sic/export', $this->headersFor('scale', $user))
            ->assertTooManyRequests()->assertJsonPath('error.code', 'quota_exceeded');
    }

    public function test_export_returns_json_and_no_snapshot_when_usage_accounting_is_unavailable(): void
    {
        $headers = $this->headersFor('scale');
        Schema::drop('monthly_usage');

        $this->get('/v1/sic/export', $headers)
            ->assertStatus(503)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Retry-After', '30')
            ->assertJsonPath('error.code', 'usage_unavailable');
        $this->assertDatabaseCount('dataset_snapshots', 0);
    }

    public function test_export_rejects_query_parameters_and_oversized_snapshots(): void
    {
        $headers = $this->headersFor('scale');
        $this->get('/v1/sic/export?per_page=999999', $headers)->assertUnprocessable()->assertJsonPath('error.code', 'invalid_parameter');
        $records = [];
        foreach (range(1, 2001) as $code) {
            $records[] = ['code' => sprintf('%05d', $code), 'description' => 'Record'];
        }
        $this->writeSnapshot($records);
        $this->get('/v1/sic/export', $headers)->assertStatus(503)->assertJsonPath('error.code', 'sic_reference_unavailable');
    }

    /** @param list<array{code: string, description: string}> $records */
    private function writeSnapshot(array $records): void
    {
        $this->snapshotPath ??= tempnam(sys_get_temp_dir(), 'sic-export-');
        File::put($this->snapshotPath, json_encode([
            'version' => 'companies_house_condensed_sic_2007',
            'retrieved_at' => '2026-10-22T12:00:00+00:00',
            'checksum' => hash('sha256', json_encode($records, JSON_THROW_ON_ERROR)),
            'records' => $records,
        ], JSON_THROW_ON_ERROR));
        config()->set('ukapi.sic_reference.snapshot_path', $this->snapshotPath);
    }

    /** @return list<array<string, string|null>> */
    private function parseCsv(string $csv): array
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $csv);
        rewind($stream);
        $columns = fgetcsv($stream, escape: '');
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $this->assertCount(count($columns), $row);
            $rows[] = array_combine($columns, $row);
        }
        fclose($stream);

        return $rows;
    }

    /** @return array{Authorization: string} */
    private function headersFor(string $plan, ?User $user = null): array
    {
        $user ??= User::factory()->create();
        if ($plan !== 'free' && ! $user->subscriptions()->exists()) {
            config()->set("ukapi.paid_plans.{$plan}.stripe_price", 'price_'.$plan.'_export');
            $user->subscriptions()->create([
                'type' => 'default', 'stripe_id' => 'sub_'.Str::random(12),
                'stripe_status' => 'active', 'stripe_price' => 'price_'.$plan.'_export',
            ]);
        }
        $secret = Str::lower(Str::random(43));
        $key = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)), 'secret_hash' => Hash::make($secret),
            'name' => 'SIC export test', 'environment' => 'test', 'status' => 'active',
        ]);

        return ['Authorization' => "Bearer uk_test_{$key->public_id}.{$secret}"];
    }
}

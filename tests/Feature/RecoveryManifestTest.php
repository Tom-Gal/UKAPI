<?php

namespace Tests\Feature;

use App\Domain\Companies\SicReference;
use App\Domain\Operations\RecoveryManifest;
use App\Domain\Snapshots\SnapshotStore;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class RecoveryManifestTest extends TestCase
{
    use RefreshDatabase;

    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifestPath = storage_path('framework/testing/recovery-manifest.json');
        File::delete($this->manifestPath);
        app(SnapshotStore::class)->capture(
            'companies_house.sic_2007',
            'reference',
            'companies_house',
            [['code' => '62012', 'description' => 'Business and domestic software development']],
            CarbonImmutable::parse('2026-10-27T12:00:00+00:00'),
        );
    }

    protected function tearDown(): void
    {
        File::delete($this->manifestPath);
        parent::tearDown();
    }

    public function test_recovery_manifest_covers_critical_state_without_exposing_sensitive_values(): void
    {
        $user = User::factory()->create([
            'email' => 'private@example.test',
            'stripe_id' => 'cus_private_identifier',
        ]);
        $user->apiKeys()->create([
            'public_id' => 'recovery-key',
            'secret_hash' => 'private-secret-hash',
            'name' => 'Recovery key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        $this->artisan('ukapi:recovery:verify', ['--write' => $this->manifestPath])->assertSuccessful();

        $manifest = File::get($this->manifestPath);
        $this->assertStringContainsString('"sic_active"', $manifest);
        $this->assertStringContainsString('"monthly_requests"', $manifest);
        $this->assertStringNotContainsString('private@example.test', $manifest);
        $this->assertStringNotContainsString('cus_private_identifier', $manifest);
        $this->assertStringNotContainsString('private-secret-hash', $manifest);

        $decoded = json_decode($manifest, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($decoded['valid']);
        $this->assertSame(1, $decoded['checks']['snapshots']['sic_active']['record_count']);
    }

    public function test_restore_comparison_fails_when_critical_counts_change(): void
    {
        $this->artisan('ukapi:recovery:verify', ['--write' => $this->manifestPath])->assertSuccessful();
        User::factory()->create();

        $this->artisan('ukapi:recovery:verify', ['--baseline' => $this->manifestPath])
            ->expectsOutput('The restored database does not match the baseline recovery checks.')
            ->assertFailed();
    }

    public function test_cloud_friendly_checksum_comparison_does_not_require_a_shared_file(): void
    {
        $manifest = app(RecoveryManifest::class)->generate();
        $checksum = hash('sha256', json_encode(
            $manifest['checks'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ));

        $this->artisan('ukapi:recovery:verify', [
            '--checksum-only' => true,
            '--expect-checksum' => $checksum,
        ])->expectsOutput('The restored database matches the expected recovery checksum.')
            ->assertSuccessful();
    }

    public function test_sic_reference_prefers_the_durable_mysql_snapshot_over_local_files(): void
    {
        config()->set([
            'ukapi.sic_reference.snapshot_path' => base_path('tests/Fixtures/missing-sic.json'),
            'ukapi.sic_reference.seed_snapshot_path' => base_path('tests/Fixtures/missing-sic-seed.json'),
        ]);

        $snapshot = app(SicReference::class)->snapshot();

        $this->assertSame('62012', $snapshot->records[0]->code);
        $this->assertSame('2026-10-27T12:00:00+00:00', $snapshot->retrievedAt);
    }
}

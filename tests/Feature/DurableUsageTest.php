<?php

namespace Tests\Feature;

use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use App\Models\User;
use App\Notifications\UsageLimitNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DurableUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_quota_and_alert_deduplication_survive_cache_loss_and_new_meter_instances(): void
    {
        Notification::fake();
        config(['ukapi.free_plan.monthly_request_quota' => 2, 'ukapi.usage_notification_thresholds' => '50']);
        [$key, $token] = $this->key();
        $this->withToken($token)->getJson('/v1/vat/calculate?amount=100&rate=20')->assertOk();
        Cache::flush();
        $this->assertSame(1, (new UsageMeter)->currentMonthlyUsage($key));
        $this->withToken($token)->getJson('/v1/vat/calculate?amount=100&rate=20')->assertOk();
        Cache::flush();
        $this->withToken($token)->getJson('/v1/vat/calculate?amount=100&rate=20')
            ->assertStatus(429)->assertJsonPath('error.code', 'quota_exceeded');
        $this->assertSame(2, (new UsageMeter)->currentMonthlyUsage($key));
        Notification::assertSentTo($key->user, UsageLimitNotification::class, 1);
    }

    public function test_rejected_requests_do_not_increment_totals_or_endpoint_breakdown(): void
    {
        [$key] = $this->key();
        $meter = new UsageMeter;
        $this->assertSame(1, $meter->reserve($key, 'vat.calculate', 1));
        $this->assertNull($meter->reserve($key, 'vat.remove', 1));
        $this->assertNull($meter->reserve($key, 'vat.remove', 1));
        $this->assertSame(1, $meter->currentMonthlyUsage($key));
        $this->assertSame([['endpoint' => 'vat.calculate', 'label' => 'VAT calculations', 'requests' => 1]],
            $meter->currentMonthlyBreakdownForUser($key->user_id));
        // A higher plan admits the next request without resetting usage.
        $this->assertSame(2, $meter->reserve($key, 'vat.remove', 2));
    }

    public function test_monthly_rollover_uses_utc_and_keeps_prior_usage(): void
    {
        [$key] = $this->key();
        $meter = new UsageMeter;
        $this->travelTo(CarbonImmutable::parse('2026-10-31T23:59:59Z'));
        $admitted = CarbonImmutable::now('UTC');
        $this->assertSame(1, $meter->reserve($key, 'vat.calculate', 1, $admitted));
        $this->travelTo(CarbonImmutable::parse('2026-11-01T00:00:01Z'));
        $this->assertSame(0, $meter->currentMonthlyUsage($key));
        $this->assertSame(1, $meter->reserve($key, 'vat.calculate', 1));
        $this->assertNull($meter->reserve($key, 'vat.calculate', 1, $admitted));
        $this->assertSame(1793491200, $meter->monthlyResetAt($admitted));
        $this->assertDatabaseHas('monthly_usage', ['user_id' => $key->user_id, 'month' => '2026-10-01', 'requests' => 1]);
        $this->assertDatabaseHas('monthly_usage', ['user_id' => $key->user_id, 'month' => '2026-11-01', 'requests' => 1]);
        $this->assertSame('2026-10-01', $meter->periodStart(CarbonImmutable::parse('2026-11-01T00:30:00+01:00'))->toDateString());
        $this->travelBack();
    }

    public function test_breakdown_storage_failure_rolls_back_the_reservation(): void
    {
        [$key] = $this->key();
        Schema::rename('monthly_endpoint_usage', 'unavailable_endpoint_usage');
        try {
            (new UsageMeter)->reserve($key, 'vat.calculate', 5);
            $this->fail('The reservation must fail when its transaction cannot commit.');
        } catch (QueryException) {
            $this->assertSame(0, (new UsageMeter)->currentMonthlyUsage($key));
        } finally {
            Schema::rename('unavailable_endpoint_usage', 'monthly_endpoint_usage');
        }
    }

    public function test_accounting_outage_returns_503_without_running_the_endpoint(): void
    {
        [, $token] = $this->key();
        Schema::rename('monthly_usage', 'unavailable_monthly_usage');
        try {
            $this->withToken($token)->getJson('/v1/vat/calculate?amount=100&rate=20')
                ->assertStatus(503)->assertJsonPath('error.code', 'usage_unavailable')
                ->assertHeader('Retry-After', '30')->assertJsonMissingPath('data');
            $this->assertSame(0, DB::table('monthly_endpoint_usage')->count());
        } finally {
            Schema::rename('unavailable_monthly_usage', 'monthly_usage');
        }
    }

    public function test_legacy_import_is_idempotent_and_never_reduces_existing_usage(): void
    {
        [$key] = $this->key();
        $prefix = sprintf('ukapi:usage:%s:account:%d', now('UTC')->format('Ym'), $key->user_id);
        Cache::store('database')->put($prefix, 4, 3600);
        Cache::store('database')->put($prefix.':endpoint:vat.calculate', 4, 3600);
        $this->artisan('ukapi:usage:import-cache', ['--store' => 'database'])->assertSuccessful();
        $this->artisan('ukapi:usage:import-cache', ['--store' => 'database'])->assertSuccessful();
        $meter = new UsageMeter;
        $this->assertSame(4, $meter->currentMonthlyUsage($key));
        $this->assertSame(5, $meter->reserve($key, 'vat.calculate', 5));
        $this->artisan('ukapi:usage:import-cache', ['--store' => 'database'])->assertSuccessful();
        $this->assertSame(5, $meter->currentMonthlyUsage($key));
        $this->assertSame(5, $meter->currentMonthlyBreakdownForUser($key->user_id)[0]['requests']);
        $this->artisan('ukapi:usage:import-cache', ['--store' => 'array'])->assertFailed();
    }

    /** @return array{ApiKey, string} */
    private function key(): array
    {
        $secret = Str::random(43);
        $key = User::factory()->create()->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Quota test',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return [$key, "uk_test_{$key->public_id}.{$secret}"];
    }
}

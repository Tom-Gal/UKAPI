<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class UsageInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_page_reports_daily_trend_projection_comparison_and_rejections(): void
    {
        $this->travelTo('2026-10-10 10:00:00');
        config()->set('ukapi.free_plan.monthly_request_quota', 2);
        config()->set('ukapi.free_plan.burst_requests_per_second', 1);
        [$user, $token] = $this->key();
        DB::table('monthly_usage')->insert([
            'user_id' => $user->id,
            'month' => '2026-09-01',
            'requests' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)->assertOk();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', $headers)
            ->assertTooManyRequests()->assertJsonPath('error.code', 'rate_limit_exceeded');
        $this->travel(1)->seconds();
        $this->getJson('/v1/vat/remove?amount=120&rate=20', $headers)->assertOk();
        $this->travel(1)->seconds();
        $this->getJson('/v1/vat/remove?amount=120&rate=20', $headers)
            ->assertTooManyRequests()->assertJsonPath('error.code', 'quota_exceeded');

        $this->actingAs($user)->get(route('usage'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('dashboard/usage')
                ->where('monthlyUsage', 2)
                ->where('insights.rejections.rate_limit', 1)
                ->where('insights.rejections.quota', 1)
                ->where('insights.projection.requests', 6)
                ->where('insights.previousMonth.requests', 1)
                ->where('insights.previousMonth.difference', 1)
                ->where('insights.upgradeRecommended', true)
                ->where('insights.dailyTrackingStartedAt', '2026-10-10')
                ->where('insights.dailyTrend.0.requests', 2)
                ->where('insights.dailyTrend.0.rate_limit_rejections', 1)
                ->where('insights.dailyTrend.0.quota_rejections', 1)
                ->has('breakdown', 2));

        $this->travelBack();
    }

    public function test_aggregate_csv_contains_no_request_values_or_credentials(): void
    {
        $this->travelTo('2026-10-10 10:00:00');
        [$user, $token] = $this->key();
        $sensitiveAmount = '98765.43';
        $this->getJson("/v1/vat/calculate?amount={$sensitiveAmount}&rate=20", [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $response = $this->actingAs($user)->get(route('usage.export'));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('vat.calculate', $csv);
        $this->assertStringContainsString('Daily total', $csv);
        $this->assertStringNotContainsString($sensitiveAmount, $csv);
        $this->assertStringNotContainsString($token, $csv);

        $this->travelBack();
    }

    /** @return array{User, string} */
    private function key(): array
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $key = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Usage insights',
            'environment' => 'test',
        ]);

        return [$user, "uk_test_{$key->public_id}.{$secret}"];
    }
}

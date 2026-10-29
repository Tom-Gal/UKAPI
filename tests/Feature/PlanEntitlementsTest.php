<?php

namespace Tests\Feature;

use App\Domain\Billing\PlanCatalog;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnforceApiRateLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlanEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_durable_catalog_exposes_the_expected_plan_matrix(): void
    {
        $this->configureTestPrices();
        $catalog = app(PlanCatalog::class);

        $this->assertSame([
            'free' => ['current_data'],
            'hobby' => ['current_data'],
            'pro' => ['current_data', 'historical_data', 'change_feeds', 'commercial_use', 'ip_allow_lists'],
            'scale' => ['current_data', 'historical_data', 'change_feeds', 'commercial_use', 'bulk_export', 'ip_allow_lists'],
        ], collect([$catalog->free(), ...$catalog->paid()])
            ->mapWithKeys(fn (array $plan): array => [$plan['key'] => $plan['features']])
            ->all());

        $this->assertSame(25, $catalog->free()['max_page_size']);
        $this->assertSame(50, $catalog->checkoutable('hobby')['max_page_size']);
        $this->assertSame(100, $catalog->checkoutable('pro')['max_page_size']);
        $this->assertSame(100, $catalog->checkoutable('scale')['max_page_size']);
    }

    #[DataProvider('featurePlans')]
    public function test_the_feature_middleware_allows_only_the_plans_that_have_a_feature(
        string $plan,
        ?string $stripePrice,
        bool $allowed,
    ): void {
        Route::middleware([
            AuthenticateApiKey::class,
            'ukapi.feature:historical_data',
            EnforceApiRateLimit::class,
        ])->get('/v1/test-plan-feature', static fn () => response()->json(['ok' => true]));

        $this->configureTestPrices();

        $user = User::factory()->create();
        if ($stripePrice !== null) {
            $user->subscriptions()->create([
                'type' => 'default',
                'stripe_id' => 'sub_'.$plan.'_matrix',
                'stripe_status' => 'active',
                'stripe_price' => $stripePrice,
            ]);
        }

        [, $token] = $this->createApiKey($user);
        $response = $this->getJson('/v1/test-plan-feature', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus($allowed ? 200 : 403);
        if (! $allowed) {
            $response->assertJsonPath('error.code', 'feature_not_available');
            $response->assertJsonPath('error.details.feature', 'historical_data');
        }
    }

    /** @return iterable<string, array{string, ?string, bool}> */
    public static function featurePlans(): iterable
    {
        yield 'free plan is denied' => ['free', null, false];
        yield 'hobby plan is denied' => ['hobby', 'price_hobby_matrix', false];
        yield 'pro plan is allowed' => ['pro', 'price_pro_matrix', true];
        yield 'scale plan is allowed' => ['scale', 'price_scale_matrix', true];
    }

    public function test_annual_prices_are_supported_without_changing_stable_plan_keys(): void
    {
        $this->configureTestPrices();

        $plan = app(PlanCatalog::class)->checkoutable('pro', 'year');

        $this->assertSame('pro', $plan['key']);
        $this->assertSame('price_pro_matrix_annual', $plan['stripe_price']);
    }

    public function test_public_plan_projection_matches_entitlements_prices_and_checkout_availability(): void
    {
        $this->configureTestPrices();
        $catalog = app(PlanCatalog::class);

        $this->assertTrue($catalog->hasConfiguredPrices());
        $this->assertTrue($catalog->hasConfiguredAnnualPrices());

        foreach ($catalog->publicPlans() as $publicPlan) {
            $runtimePlan = $publicPlan['key'] === 'free'
                ? $catalog->free()
                : $catalog->checkoutable($publicPlan['key']);

            $this->assertIsArray($runtimePlan);
            $this->assertSame(
                $runtimePlan['prices']['month']['amount'] ?? 0,
                $publicPlan['monthly_price_pence'],
                $publicPlan['key'].' monthly price',
            );
            $this->assertSame(
                $runtimePlan['prices']['year']['amount'] ?? 0,
                $publicPlan['annual_price_pence'],
                $publicPlan['key'].' annual price',
            );
            $this->assertSame(
                $runtimePlan['monthly_request_quota'],
                $publicPlan['monthly_request_quota'],
                $publicPlan['key'].' monthly quota',
            );
            $this->assertSame(
                $runtimePlan['burst_requests_per_second'],
                $publicPlan['burst_requests_per_second'],
                $publicPlan['key'].' burst limit',
            );
            $this->assertSame(
                $runtimePlan['max_page_size'],
                $publicPlan['max_page_size'],
                $publicPlan['key'].' page size',
            );
            $this->assertSame(
                $runtimePlan['features'],
                $publicPlan['features'],
                $publicPlan['key'].' features',
            );
            $this->assertSame(
                $runtimePlan['commercial_use'],
                $publicPlan['commercial_use'],
                $publicPlan['key'].' commercial use',
            );
            $this->assertArrayNotHasKey('stripe_price', $publicPlan);
            $this->assertArrayNotHasKey('prices', $publicPlan);
        }
    }

    /** @return array{0: mixed, 1: string} */
    private function createApiKey(User $user): array
    {
        $secret = Str::lower(Str::random(43));
        $apiKey = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Plan matrix key',
            'environment' => 'test',
            'status' => 'active',
        ]);

        return [$apiKey, "uk_test_{$apiKey->public_id}.{$secret}"];
    }

    private function configureTestPrices(): void
    {
        config()->set([
            'ukapi.paid_plans.hobby.stripe_price' => 'price_hobby_matrix',
            'ukapi.paid_plans.hobby.annual_stripe_price' => 'price_hobby_matrix_annual',
            'ukapi.paid_plans.pro.stripe_price' => 'price_pro_matrix',
            'ukapi.paid_plans.pro.annual_stripe_price' => 'price_pro_matrix_annual',
            'ukapi.paid_plans.scale.stripe_price' => 'price_scale_matrix',
            'ukapi.paid_plans.scale.annual_stripe_price' => 'price_scale_matrix_annual',
        ]);
    }
}

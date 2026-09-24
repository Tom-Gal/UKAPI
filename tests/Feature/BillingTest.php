<?php

namespace Tests\Feature;

use App\Domain\Usage\Entitlements;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_stripe_subscription_selects_the_matching_api_entitlement(): void
    {
        config()->set('ukapi.paid_plans.pro.stripe_price', 'price_pro_test');

        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_pro',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
        ]);

        $plan = app(Entitlements::class)->forUser($user);

        $this->assertSame('Pro', $plan['label']);
        $this->assertSame(250_000, $plan['monthly_request_quota']);
        $this->assertSame(25, $plan['burst_requests_per_second']);
    }

    public function test_billing_page_shows_the_active_stripe_plan(): void
    {
        config()->set('ukapi.paid_plans.hobby.stripe_price', 'price_hobby_test');

        $user = User::factory()->create();
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test_hobby',
            'stripe_status' => 'active',
            'stripe_price' => 'price_hobby_test',
        ]);

        $this->actingAs($user)
            ->get(route('billing'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/billing')
                ->where('plan.label', 'Hobby')
                ->where('plan.monthly_request_quota', 50_000)
                ->where('hasActiveSubscription', true),
            );
    }

    public function test_checkout_rejects_an_unknown_plan_without_contacting_stripe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('billing.checkout'), ['plan' => 'enterprise'])
            ->assertSessionHasErrors('plan');
    }
}

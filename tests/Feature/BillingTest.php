<?php

namespace Tests\Feature;

use App\Domain\Usage\Entitlements;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Events\WebhookHandled;
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

    public function test_stripe_subscription_webhook_updates_the_local_entitlement_record(): void
    {
        Notification::fake();
        config()->set('cashier.webhook.secret', null);
        config()->set('ukapi.paid_plans.pro.stripe_price', 'price_pro_test');

        $user = User::factory()->create(['stripe_id' => 'cus_webhook_test']);

        $this->postJson(route('cashier.webhook'), [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_webhook_test',
                    'customer' => 'cus_webhook_test',
                    'status' => 'active',
                    'metadata' => ['type' => 'default'],
                    'items' => [
                        'data' => [[
                            'id' => 'si_webhook_test',
                            'quantity' => 1,
                            'price' => [
                                'id' => 'price_pro_test',
                                'product' => 'prod_webhook_test',
                            ],
                        ]],
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertTrue($user->fresh()->subscribed('default', 'price_pro_test'));
        $this->assertSame('Pro', app(Entitlements::class)->forUser($user->fresh())['label']);
        Notification::assertSentTo($user, SubscriptionNotification::class);
    }

    public function test_a_scheduled_subscription_cancellation_sends_an_email(): void
    {
        Notification::fake();
        config()->set('ukapi.paid_plans.pro.stripe_price', 'price_pro_test');
        $user = User::factory()->create(['stripe_id' => 'cus_cancel_test']);

        event(new WebhookHandled([
            'type' => 'customer.subscription.updated',
            'data' => [
                'previous_attributes' => ['cancel_at_period_end' => false],
                'object' => [
                    'customer' => 'cus_cancel_test',
                    'cancel_at_period_end' => true,
                    'items' => ['data' => [[
                        'current_period_end' => now()->addMonth()->timestamp,
                        'price' => ['id' => 'price_pro_test'],
                    ]]],
                ],
            ],
        ]));

        Notification::assertSentTo($user, SubscriptionNotification::class);
    }
}

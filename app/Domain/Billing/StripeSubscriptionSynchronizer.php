<?php

namespace App\Domain\Billing;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription as CashierSubscription;
use Stripe\Checkout\Session;
use Stripe\Subscription as StripeSubscription;

final class StripeSubscriptionSynchronizer
{
    /**
     * Synchronize a completed Checkout subscription before its webhook is delivered.
     *
     * The Checkout Session is fetched from Stripe and must belong to the authenticated
     * customer, so a customer cannot synchronize somebody else's subscription.
     */
    public function synchronizeCheckoutSession(User $user, string $checkoutSessionId): bool
    {
        if (! $user->hasStripeId()) {
            return false;
        }

        /** @var Session $session */
        $session = Cashier::stripe()->checkout->sessions->retrieve($checkoutSessionId, [
            'expand' => ['subscription'],
        ]);

        if (
            $session->mode !== 'subscription'
            || $session->status !== 'complete'
            || ! in_array($session->payment_status, ['paid', 'no_payment_required'], true)
            || $session->customer !== $user->stripe_id
            || ! $session->subscription
        ) {
            return false;
        }

        $subscription = $session->subscription instanceof StripeSubscription
            ? $session->subscription
            : Cashier::stripe()->subscriptions->retrieve($session->subscription);

        $this->synchronizeSubscription($user, $subscription);

        return true;
    }

    public function synchronizeSubscription(User $user, StripeSubscription $stripeSubscription): void
    {
        $items = $stripeSubscription->items->data;
        $firstItem = $items[0] ?? null;
        $isSinglePrice = count($items) === 1 && $firstItem !== null;

        $subscription = $user->subscriptions()->firstOrNew([
            'stripe_id' => $stripeSubscription->id,
        ]);

        if (! $subscription instanceof CashierSubscription) {
            throw new \LogicException('The configured Cashier subscription model is invalid.');
        }

        $subscription->fill([
            'type' => $stripeSubscription->metadata['type'] ?? $stripeSubscription->metadata['name'] ?? 'default',
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $isSinglePrice ? $firstItem->price->id : null,
            'quantity' => $isSinglePrice ? $firstItem->quantity : null,
            'trial_ends_at' => $stripeSubscription->trial_end
                ? Carbon::createFromTimestamp($stripeSubscription->trial_end)
                : null,
            'ends_at' => $stripeSubscription->cancel_at_period_end && $firstItem !== null
                ? Carbon::createFromTimestamp($firstItem->current_period_end)
                : null,
        ]);
        $subscription->save();

        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item->id;
            $subscription->items()->updateOrCreate([
                'stripe_id' => $item->id,
            ], [
                'stripe_product' => $item->price->product,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity,
            ]);
        }

        $subscription->items()->whereNotIn('stripe_id', $itemIds)->delete();
    }
}

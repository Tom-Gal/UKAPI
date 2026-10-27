<?php

namespace App\Listeners;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Operations\SafeNotificationSender;
use App\Models\User;
use App\Notifications\SubscriptionNotification;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Events\WebhookHandled;

final class SendSubscriptionLifecycleNotification
{
    public function __construct(
        private readonly PlanCatalog $plans,
        private readonly SafeNotificationSender $notifications,
    ) {}

    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;
        $subscription = $payload['data']['object'] ?? null;

        if (! is_array($subscription) || ! is_string($subscription['customer'] ?? null)) {
            return;
        }

        $user = User::query()->where('stripe_id', $subscription['customer'])->first();

        if ($user === null) {
            return;
        }

        $planLabel = $this->planLabel($subscription);

        if ($type === 'customer.subscription.created') {
            $this->notifications->send($user, SubscriptionNotification::confirmed($planLabel));

            return;
        }

        if ($type === 'customer.subscription.deleted') {
            $this->notifications->send($user, SubscriptionNotification::cancelled($planLabel));

            return;
        }

        if ($type !== 'customer.subscription.updated') {
            return;
        }

        $previous = $payload['data']['previous_attributes'] ?? [];

        if (! is_array($previous)) {
            return;
        }

        if (array_key_exists('cancel_at_period_end', $previous) && ($subscription['cancel_at_period_end'] ?? false)) {
            $this->notifications->send($user, SubscriptionNotification::cancellationScheduled(
                $planLabel,
                $this->cancellationDate($subscription),
            ));

            return;
        }

        if (array_key_exists('items', $previous)) {
            $this->notifications->send($user, SubscriptionNotification::changed($planLabel));
        }
    }

    /** @param array<string, mixed> $subscription */
    private function planLabel(array $subscription): string
    {
        $price = $subscription['items']['data'][0]['price']['id'] ?? null;

        return is_string($price) ? ($this->plans->forStripePrice($price)['label'] ?? 'paid') : 'paid';
    }

    /** @param array<string, mixed> $subscription */
    private function cancellationDate(array $subscription): ?string
    {
        $timestamp = $subscription['items']['data'][0]['current_period_end'] ?? $subscription['cancel_at'] ?? null;

        return is_int($timestamp)
            ? Carbon::createFromTimestamp($timestamp)->format('j F Y')
            : null;
    }
}

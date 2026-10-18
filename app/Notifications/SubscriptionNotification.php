<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SubscriptionNotification extends Notification
{
    use Queueable;

    private function __construct(
        private readonly string $event,
        private readonly string $planLabel,
        private readonly ?string $effectiveAt = null,
    ) {}

    public static function confirmed(string $planLabel): self
    {
        return new self('confirmed', $planLabel);
    }

    public static function changed(string $planLabel): self
    {
        return new self('changed', $planLabel);
    }

    public static function cancellationScheduled(string $planLabel, ?string $effectiveAt): self
    {
        return new self('cancellation_scheduled', $planLabel, $effectiveAt);
    }

    public static function cancelled(string $planLabel): self
    {
        return new self('cancelled', $planLabel);
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        [$subject, $heading, $body] = match ($this->event) {
            'confirmed' => [
                'Your UKApi.co.uk subscription is active',
                'Your subscription is active',
                "Your {$this->planLabel} plan is ready to use. Your account limits have been updated.",
            ],
            'changed' => [
                'Your UKApi.co.uk plan has changed',
                'Your plan has changed',
                "Your account is now on the {$this->planLabel} plan. Your API limits have been updated.",
            ],
            'cancellation_scheduled' => [
                'Your UKApi.co.uk subscription will end',
                'Your subscription cancellation is scheduled',
                $this->effectiveAt
                    ? "Your {$this->planLabel} plan remains available until {$this->effectiveAt}."
                    : "Your {$this->planLabel} plan will remain available until the end of its current billing period.",
            ],
            default => [
                'Your UKApi.co.uk subscription has ended',
                'Your subscription has ended',
                "Your {$this->planLabel} subscription has ended and your account is now on the Free plan.",
            ],
        };

        $data = [
            'name' => $notifiable->name,
            'heading' => $heading,
            'body' => $body,
            'billingUrl' => url('/billing'),
        ];

        return (new MailMessage)
            ->subject($subject.' · UKApi.co.uk')
            ->view('emails.billing.subscription', $data)
            ->text('emails.billing.subscription-text', $data);
    }
}

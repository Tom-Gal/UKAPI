<?php

namespace App\Domain\Health;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class HealthAlert extends Notification
{
    public function __construct(
        public readonly string $service,
        public readonly bool $recovered,
        public readonly ?string $failureReason = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = HealthStatus::SERVICES[$this->service];

        return (new MailMessage)
            ->subject('UKAPI health: '.$name.($this->recovered ? ' recovered' : ' check failed'))
            ->line($this->recovered ? 'The latest scheduled availability check passed.' : 'Repeated scheduled availability checks failed.')
            ->line('Service: '.$name)
            ->when(! $this->recovered, fn (MailMessage $message) => $message->line('Failure type: '.($this->failureReason ?? 'probe_failed')))
            ->line('Checked at: '.now()->toIso8601String())
            ->line('This is a sampled availability check. Source freshness is reported separately on the status page.')
            ->action('View status', url('/status'));
    }
}

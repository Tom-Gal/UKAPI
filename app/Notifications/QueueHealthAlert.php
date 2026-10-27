<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * This alert deliberately remains synchronous. It runs only from the scheduler
 * and must not depend on the queue whose failure it reports.
 */
final class QueueHealthAlert extends Notification
{
    public function __construct(
        public readonly bool $recovered,
        public readonly int $pending,
        public readonly int $failed,
        public readonly ?int $oldestAgeSeconds,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->recovered ? 'UKAPI queue recovered' : 'UKAPI queue requires attention')
            ->line($this->recovered
                ? 'The database-backed mail queue has returned within its configured operating thresholds.'
                : 'The database-backed mail queue has exceeded a configured operating threshold.')
            ->line('Pending jobs: '.$this->pending)
            ->line('Failed jobs: '.$this->failed)
            ->line('Oldest pending job: '.($this->oldestAgeSeconds === null ? 'none' : $this->oldestAgeSeconds.' seconds'))
            ->line('Checked at: '.now()->toIso8601String());

        if (! $this->recovered) {
            $message->line('Inspect the Laravel Cloud worker cluster, application logs, and the admin operations queue panel before retrying or deleting jobs.');
        }

        return $message;
    }
}

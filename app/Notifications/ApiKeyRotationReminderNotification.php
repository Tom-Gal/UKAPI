<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Concerns\QueuesMail;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ApiKeyRotationReminderNotification extends Notification implements ShouldQueueAfterCommit
{
    use QueuesMail;

    /** @param list<array{name: string, environment: string, ageDays: int}> $keys */
    public function __construct(private readonly array $keys) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $data = [
            'name' => $notifiable->name,
            'keys' => $this->keys,
            'apiKeysUrl' => url('/api-keys'),
        ];

        return (new MailMessage)
            ->subject('Rotate your UKApi.co.uk API key'.(count($this->keys) === 1 ? '' : 's'))
            ->view('emails.api-keys.rotation-reminder', $data)
            ->text('emails.api-keys.rotation-reminder-text', $data);
    }
}

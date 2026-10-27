<?php

namespace App\Notifications;

use App\Models\ApiKey;
use App\Models\User;
use App\Notifications\Concerns\QueuesMail;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ApiKeyActivityNotification extends Notification implements ShouldQueueAfterCommit
{
    use QueuesMail;

    private function __construct(
        private readonly string $keyName,
        private readonly string $environment,
        private readonly string $activity,
    ) {}

    public static function created(ApiKey $apiKey): self
    {
        return new self($apiKey->name, $apiKey->environment, 'created');
    }

    public static function revoked(ApiKey $apiKey): self
    {
        return new self($apiKey->name, $apiKey->environment, 'revoked');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $created = $this->activity === 'created';
        $message = (new MailMessage)
            ->subject($created ? 'New API key created · UKApi.co.uk' : 'API key revoked · UKApi.co.uk');
        $data = [
            'name' => $notifiable->name,
            'keyName' => $this->keyName,
            'environment' => ucfirst($this->environment),
            'apiKeysUrl' => url('/api-keys'),
        ];

        if ($created) {
            return $message
                ->view('emails.api-keys.created', $data)
                ->text('emails.api-keys.created-text', $data);
        }

        return $message
            ->view('emails.api-keys.revoked', $data)
            ->text('emails.api-keys.revoked-text', $data);
    }

    protected function mailQueueName(): string
    {
        return (string) config('ukapi.notifications.critical_queue', 'mail-critical');
    }
}

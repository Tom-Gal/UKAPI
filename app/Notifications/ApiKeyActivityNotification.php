<?php

namespace App\Notifications;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ApiKeyActivityNotification extends Notification
{
    use Queueable;

    private function __construct(
        private readonly ApiKey $apiKey,
        private readonly string $activity,
    ) {}

    public static function created(ApiKey $apiKey): self
    {
        return new self($apiKey, 'created');
    }

    public static function revoked(ApiKey $apiKey): self
    {
        return new self($apiKey, 'revoked');
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $created = $this->activity === 'created';

        return (new MailMessage)
            ->subject($created ? 'New API key created · UKAPI.io' : 'API key revoked · UKAPI.io')
            ->view([
                'html' => "emails.api-keys.{$this->activity}",
                'text' => "emails.api-keys.{$this->activity}-text",
            ], [
                'name' => $notifiable->name,
                'keyName' => $this->apiKey->name,
                'environment' => ucfirst($this->apiKey->environment),
                'apiKeysUrl' => url('/api-keys'),
            ]);
    }
}

<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $heading,
        public readonly string $alertMessage,
    ) {}

    public static function passwordChanged(): self
    {
        return new self(
            'Your password was changed',
            'The password for your UKAPI.io account has just been changed. If this was not you, reset your password immediately and contact support.',
        );
    }

    public static function twoFactorEnabled(): self
    {
        return new self(
            'Two-factor authentication is active',
            'A new authenticator app has been confirmed for your UKAPI.io account. Your recovery codes remain available in Security settings.',
        );
    }

    public static function twoFactorDisabled(): self
    {
        return new self(
            'Two-factor authentication was turned off',
            'Two-factor authentication has been disabled on your UKAPI.io account. If this was not you, reset your password immediately and contact support.',
        );
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->heading.' · UKAPI.io security')
            ->view([
                'html' => 'emails.security.alert',
                'text' => 'emails.security.alert-text',
            ], [
                'name' => $notifiable->name,
                'heading' => $this->heading,
                'alertMessage' => $this->alertMessage,
                'securityUrl' => url('/settings/security'),
            ]);
    }
}

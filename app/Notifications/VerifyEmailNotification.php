<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuesMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;

final class VerifyEmailNotification extends VerifyEmail implements ShouldQueueAfterCommit
{
    use QueuesMail;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your UKApi.co.uk email address')
            ->view([
                'html' => 'emails.auth.verify-email',
                'text' => 'emails.auth.verify-email-text',
            ], [
                'name' => $notifiable->name,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.verification.expire', 60),
            ]);
    }

    protected function mailQueueName(): string
    {
        return (string) config('ukapi.notifications.critical_queue', 'mail-critical');
    }
}

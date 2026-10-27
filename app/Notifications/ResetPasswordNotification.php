<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuesMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword implements ShouldQueueAfterCommit
{
    use QueuesMail;

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your UKApi.co.uk password')
            ->view([
                'html' => 'emails.auth.reset-password',
                'text' => 'emails.auth.reset-password-text',
            ], [
                'name' => $notifiable->name,
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }

    protected function mailQueueName(): string
    {
        return (string) config('ukapi.notifications.critical_queue', 'mail-critical');
    }
}

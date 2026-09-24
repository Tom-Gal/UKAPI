<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

final class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your UKAPI.io password')
            ->view([
                'html' => 'emails.auth.reset-password',
                'text' => 'emails.auth.reset-password-text',
            ], [
                'name' => $notifiable->name,
                'resetUrl' => $this->resetUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            ]);
    }
}

<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

final class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your UKAPI.io email address')
            ->view([
                'html' => 'emails.auth.verify-email',
                'text' => 'emails.auth.verify-email-text',
            ], [
                'name' => $notifiable->name,
                'verificationUrl' => $this->verificationUrl($notifiable),
                'expiresInMinutes' => (int) config('auth.verification.expire', 60),
            ]);
    }
}

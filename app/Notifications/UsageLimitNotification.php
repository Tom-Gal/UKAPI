<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class UsageLimitNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $used,
        public readonly int $limit,
        public readonly int $percentage,
        public readonly int $resetsAt,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $atLimit = $this->used >= $this->limit;

        return (new MailMessage)
            ->subject($atLimit ? 'Your monthly UKAPI.io limit has been reached' : 'You are nearing your UKAPI.io monthly limit')
            ->view([
                'html' => 'emails.usage.limit',
                'text' => 'emails.usage.limit-text',
            ], [
                'name' => $notifiable->name,
                'used' => $this->used,
                'limit' => $this->limit,
                'percentage' => $this->percentage,
                'resetsAt' => now()->setTimestamp($this->resetsAt)->format('j F Y, H:i T'),
                'atLimit' => $atLimit,
                'dashboardUrl' => url('/dashboard'),
            ]);
    }
}

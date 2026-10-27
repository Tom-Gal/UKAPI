<?php

namespace App\Domain\Operations;

use App\Models\User;
use Illuminate\Notifications\Notification;

final class SafeNotificationSender
{
    public function send(User $user, Notification $notification): bool
    {
        try {
            $user->notify($notification);

            return true;
        } catch (\Throwable $exception) {
            report($exception);

            return false;
        }
    }
}

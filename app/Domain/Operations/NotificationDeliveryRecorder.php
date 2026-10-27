<?php

namespace App\Domain\Operations;

use App\Models\NotificationDeliveryEvent;
use App\Models\User;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

final class NotificationDeliveryRecorder
{
    public function sent(NotificationSent $event): void
    {
        $this->store(
            notifiable: $event->notifiable,
            notification: $event->notification,
            channel: $event->channel,
            status: 'sent',
        );
    }

    public function failed(NotificationFailed $event): void
    {
        $exception = $event->data['exception'] ?? null;
        $this->store(
            notifiable: $event->notifiable,
            notification: $event->notification,
            channel: $event->channel,
            status: 'failed',
            failureType: $exception instanceof \Throwable ? $exception::class : null,
        );
    }

    private function store(
        mixed $notifiable,
        object $notification,
        string $channel,
        string $status,
        ?string $failureType = null,
    ): void {
        try {
            NotificationDeliveryEvent::query()->create([
                'user_id' => $notifiable instanceof User ? $notifiable->id : null,
                'notification' => class_basename($notification),
                'channel' => mb_substr($channel, 0, 32),
                'status' => $status,
                'failure_type' => $failureType ? mb_substr($failureType, 0, 160) : null,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Delivery observability must not alter notification behaviour.
            report($exception);
        }
    }
}

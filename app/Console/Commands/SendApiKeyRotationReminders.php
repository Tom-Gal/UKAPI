<?php

namespace App\Console\Commands;

use App\Domain\Operations\SafeNotificationSender;
use App\Models\User;
use App\Notifications\ApiKeyRotationReminderNotification;
use Illuminate\Console\Command;

final class SendApiKeyRotationReminders extends Command
{
    protected $signature = 'ukapi:remind-key-rotation';

    protected $description = 'Remind customers to rotate active API keys that are at least 90 days old.';

    public function handle(SafeNotificationSender $notifications): int
    {
        $reminderCutoff = now()->subDays(90);
        $resentCutoff = now()->subDays(90);
        $sent = 0;

        User::query()
            ->whereHas('apiKeys', fn ($query) => $query
                ->where('status', 'active')
                ->where('created_at', '<=', $reminderCutoff)
                ->where(fn ($query) => $query
                    ->whereNull('last_rotation_reminded_at')
                    ->orWhere('last_rotation_reminded_at', '<=', $resentCutoff)))
            ->with(['apiKeys' => fn ($query) => $query
                ->where('status', 'active')
                ->where('created_at', '<=', $reminderCutoff)
                ->where(fn ($query) => $query
                    ->whereNull('last_rotation_reminded_at')
                    ->orWhere('last_rotation_reminded_at', '<=', $resentCutoff))])
            ->each(function (User $user) use (&$sent, $notifications): void {
                $keys = $user->apiKeys;

                if ($keys->isEmpty()) {
                    return;
                }

                $keysForEmail = [];
                foreach ($keys as $key) {
                    $keysForEmail[] = [
                        'name' => $key->name,
                        'environment' => ucfirst($key->environment),
                        'ageDays' => (int) $key->created_at->diffInDays(),
                    ];
                }

                if (! $notifications->send($user, new ApiKeyRotationReminderNotification($keysForEmail))) {
                    return;
                }

                foreach ($keys as $key) {
                    $key->forceFill(['last_rotation_reminded_at' => now()])->save();
                }
                $sent++;
            });

        $this->info("Sent {$sent} API key rotation reminder(s).");

        return self::SUCCESS;
    }
}

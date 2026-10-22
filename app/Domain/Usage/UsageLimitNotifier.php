<?php

namespace App\Domain\Usage;

use App\Models\ApiKey;
use App\Notifications\UsageLimitNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class UsageLimitNotifier
{
    public function notifyIfThresholdReached(ApiKey $apiKey, int $used, int $limit, int $resetsAt): void
    {
        $user = $apiKey->user;

        if ($limit < 1 || ! $user) {
            return;
        }

        // Derive the period from the reservation, even if delivery crosses midnight.
        $month = CarbonImmutable::createFromTimestampUTC($resetsAt)->subMonth()->toDateString();
        foreach ($this->thresholds() as $percentage) {
            $threshold = (int) ceil($limit * ($percentage / 100));

            if ($used < max(1, $threshold) || ! $this->markAsSent($apiKey->user_id, $percentage, $month)) {
                continue;
            }

            try {
                $user->notify(new UsageLimitNotification(
                    used: $used,
                    limit: $limit,
                    percentage: $percentage,
                    resetsAt: $resetsAt,
                ));
            } catch (\Throwable $exception) {
                // Allow a later request to retry a failed transport.
                DB::table('usage_notifications')->where('user_id', $apiKey->user_id)
                    ->where('month', $month)->where('percentage', $percentage)->delete();
                throw $exception;
            }
        }
    }

    /** @return array<int, int> */
    private function thresholds(): array
    {
        $configured = config('ukapi.usage_notification_thresholds', '80,100');
        $values = is_array($configured) ? $configured : explode(',', (string) $configured);
        $thresholds = array_map(static fn (mixed $value): int => (int) trim((string) $value), $values);

        return array_values(array_unique(array_filter(
            $thresholds,
            static fn (int $percentage): bool => $percentage > 0 && $percentage <= 100,
        )));
    }

    private function markAsSent(int $userId, int $percentage, string $month): bool
    {
        return DB::table('usage_notifications')->insertOrIgnore([
            'user_id' => $userId,
            'month' => $month,
            'percentage' => $percentage,
            'claimed_at' => CarbonImmutable::now('UTC'),
        ]) === 1;
    }
}

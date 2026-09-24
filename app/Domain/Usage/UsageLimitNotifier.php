<?php

namespace App\Domain\Usage;

use App\Models\ApiKey;
use App\Notifications\UsageLimitNotification;
use Illuminate\Contracts\Cache\Repository;

final class UsageLimitNotifier
{
    public function __construct(private readonly Repository $cache) {}

    public function notifyIfThresholdReached(ApiKey $apiKey, int $used, int $limit, int $resetsAt): void
    {
        $user = $apiKey->user;

        if ($limit < 1 || ! $user) {
            return;
        }

        foreach ($this->thresholds() as $percentage) {
            $threshold = (int) ceil($limit * ($percentage / 100));

            if ($used < max(1, $threshold) || ! $this->markAsSent($apiKey->user_id, $percentage)) {
                continue;
            }

            $user->notify(new UsageLimitNotification(
                used: $used,
                limit: $limit,
                percentage: $percentage,
                resetsAt: $resetsAt,
            ));
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

    private function markAsSent(int $userId, int $percentage): bool
    {
        $expiresAt = now()->startOfMonth()
            ->addMonth()
            ->addDays((int) config('ukapi.usage_counter_retention_days'));

        return $this->cache->add(
            sprintf('ukapi:usage:notification:%s:account:%d:%d', now()->format('Ym'), $userId, $percentage),
            true,
            $expiresAt,
        );
    }
}

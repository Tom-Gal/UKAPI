<?php

namespace App\Domain\Usage;

use App\Models\ApiKey;
use Illuminate\Contracts\Cache\Repository;

final class UsageMeter
{
    public function __construct(private readonly Repository $cache) {}

    public function currentMonthlyUsage(ApiKey $apiKey): int
    {
        return $this->currentMonthlyUsageForUser($apiKey->user_id);
    }

    public function currentMonthlyUsageForUser(int $userId): int
    {
        return (int) $this->cache->get($this->accountKey($userId), 0);
    }

    public function record(ApiKey $apiKey, string $endpointCode): int
    {
        $expiresAt = now()->startOfMonth()
            ->addMonth()
            ->addDays((int) config('ukapi.usage_counter_retention_days'));

        $this->cache->add($this->accountKey($apiKey->user_id), 0, $expiresAt);
        $this->cache->add($this->endpointKey($apiKey, $endpointCode), 0, $expiresAt);

        $this->cache->increment($this->endpointKey($apiKey, $endpointCode));
        $this->cache->increment($this->accountKey($apiKey->user_id));

        return $this->currentMonthlyUsageForUser($apiKey->user_id);
    }

    public function monthlyResetAt(): int
    {
        return now()->startOfMonth()->addMonth()->getTimestamp();
    }

    private function accountKey(int $userId): string
    {
        return sprintf('ukapi:usage:%s:account:%d', now()->format('Ym'), $userId);
    }

    private function endpointKey(ApiKey $apiKey, string $endpointCode): string
    {
        return sprintf('ukapi:usage:%s:key:%d:%s', now()->format('Ym'), $apiKey->id, $endpointCode);
    }
}

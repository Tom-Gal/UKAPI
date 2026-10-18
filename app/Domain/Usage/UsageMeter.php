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
        $this->cache->add($this->accountEndpointKey($apiKey->user_id, $endpointCode), 0, $expiresAt);
        $this->cache->add($this->endpointKey($apiKey, $endpointCode), 0, $expiresAt);

        $this->cache->increment($this->endpointKey($apiKey, $endpointCode));
        $this->cache->increment($this->accountEndpointKey($apiKey->user_id, $endpointCode));
        $this->cache->increment($this->accountKey($apiKey->user_id));

        return $this->currentMonthlyUsageForUser($apiKey->user_id);
    }

    public function monthlyResetAt(): int
    {
        return now()->startOfMonth()->addMonth()->getTimestamp();
    }

    /**
     * @return list<array{endpoint: string, label: string, requests: int}>
     */
    public function currentMonthlyBreakdownForUser(int $userId): array
    {
        /** @var array<string, string> $labels */
        $labels = config('ukapi.usage_endpoint_labels', []);
        $breakdown = [];

        foreach ($labels as $endpoint => $label) {
            $requests = (int) $this->cache->get($this->accountEndpointKey($userId, $endpoint), 0);

            if ($requests > 0) {
                $breakdown[] = [
                    'endpoint' => $endpoint,
                    'label' => $label,
                    'requests' => $requests,
                ];
            }
        }

        usort($breakdown, static fn (array $left, array $right): int => $right['requests'] <=> $left['requests']);

        return $breakdown;
    }

    private function accountKey(int $userId): string
    {
        return sprintf('ukapi:usage:%s:account:%d', now()->format('Ym'), $userId);
    }

    private function endpointKey(ApiKey $apiKey, string $endpointCode): string
    {
        return sprintf('ukapi:usage:%s:key:%d:%s', now()->format('Ym'), $apiKey->id, $endpointCode);
    }

    private function accountEndpointKey(int $userId, string $endpointCode): string
    {
        return sprintf('ukapi:usage:%s:account:%d:endpoint:%s', now()->format('Ym'), $userId, $endpointCode);
    }
}

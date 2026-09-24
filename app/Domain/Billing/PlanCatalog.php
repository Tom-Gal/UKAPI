<?php

namespace App\Domain\Billing;

use App\Models\User;

final class PlanCatalog
{
    /**
     * @return array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function forUser(User $user): array
    {
        foreach ($this->paid() as $plan) {
            if ($plan['stripe_price'] && $user->subscribed('default', $plan['stripe_price'])) {
                return $plan;
            }
        }

        return $this->free();
    }

    /**
     * @return list<array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}>
     */
    public function paid(): array
    {
        /** @var array<string, array{label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}> $plans */
        $plans = config('ukapi.paid_plans', []);

        return array_map(
            fn (string $key, array $plan): array => ['key' => $key, ...$plan],
            array_keys($plans),
            $plans,
        );
    }

    /**
     * @return array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}|null
     */
    public function checkoutable(string $key): ?array
    {
        foreach ($this->paid() as $plan) {
            if ($plan['key'] === $key && filled($plan['stripe_price'])) {
                return $plan;
            }
        }

        return null;
    }

    public function hasConfiguredPrices(): bool
    {
        foreach ($this->paid() as $plan) {
            if (blank($plan['stripe_price'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function free(): array
    {
        /** @var array{key: string, label: string, monthly_request_quota: int, burst_requests_per_second: int} $plan */
        $plan = config('ukapi.free_plan');

        return ['stripe_price' => null, ...$plan];
    }
}

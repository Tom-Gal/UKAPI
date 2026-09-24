<?php

namespace App\Domain\Usage;

use App\Domain\Billing\PlanCatalog;
use App\Models\ApiKey;
use App\Models\User;

final class Entitlements
{
    public function __construct(private readonly PlanCatalog $plans)
    {
        //
    }

    /**
     * @return array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function forApiKey(ApiKey $apiKey): array
    {
        return $this->forUser($apiKey->user);
    }

    /**
     * @return array{key: string, label: string, stripe_price: string|null, monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function forUser(User $user): array
    {
        return $this->plans->forUser($user);
    }
}

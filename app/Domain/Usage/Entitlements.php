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

    /** @return array<string, mixed> */
    public function forApiKey(ApiKey $apiKey): array
    {
        return $this->forUser($apiKey->user);
    }

    /** @return array<string, mixed> */
    public function forUser(User $user): array
    {
        return $this->plans->forUser($user);
    }
}

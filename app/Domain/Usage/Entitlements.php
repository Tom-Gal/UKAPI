<?php

namespace App\Domain\Usage;

use App\Models\ApiKey;
use App\Models\User;

final class Entitlements
{
    /**
     * @return array{monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function forApiKey(ApiKey $apiKey): array
    {
        return $this->freePlan();
    }

    /**
     * @return array{monthly_request_quota: int, burst_requests_per_second: int}
     */
    public function forUser(User $user): array
    {
        return $this->freePlan();
    }

    /**
     * @return array{monthly_request_quota: int, burst_requests_per_second: int}
     */
    private function freePlan(): array
    {
        /** @var array{monthly_request_quota: int, burst_requests_per_second: int} $freePlan */
        $freePlan = config('ukapi.free_plan');

        return $freePlan;
    }
}

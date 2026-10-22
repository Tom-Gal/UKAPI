<?php

namespace App\Http\Middleware;

use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageLimitNotifier;
use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class EnforceMonthlyQuota
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly UsageMeter $usageMeter,
        private readonly UsageLimitNotifier $usageLimitNotifier,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $apiKey = $request->attributes->get('ukapi.api_key');

        if (! $apiKey instanceof ApiKey) {
            throw ApiException::invalidApiKey();
        }

        $plan = $request->attributes->get('ukapi.entitlements');
        $plan = is_array($plan) ? $plan : $this->entitlements->forApiKey($apiKey);
        $quota = $plan['monthly_request_quota'];

        $route = $request->route();
        $endpointCode = $route instanceof Route
            ? (string) ($route->defaults['ukapi_endpoint'] ?? 'unknown')
            : 'unknown';
        $admittedAt = CarbonImmutable::now('UTC');
        $resetAt = $this->usageMeter->monthlyResetAt($admittedAt);
        try {
            $usage = $this->usageMeter->reserve($apiKey, $endpointCode, $quota, $admittedAt);
        } catch (\Throwable $exception) {
            report($exception);
            throw ApiException::usageUnavailable();
        }

        if ($usage === null) {
            throw ApiException::quotaExceeded($quota, $resetAt);
        }

        try {
            $this->usageLimitNotifier->notifyIfThresholdReached($apiKey, $usage, $quota, $resetAt);
        } catch (\Throwable $exception) {
            // Notification transport must not turn a reserved request into an error.
            report($exception);
        }

        $response = $next($request);
        $response->headers->set('X-Quota-Limit', (string) $quota);
        $response->headers->set('X-Quota-Remaining', (string) max(0, $quota - $usage));
        $response->headers->set('X-Quota-Reset', (string) $resetAt);

        return $response;
    }
}

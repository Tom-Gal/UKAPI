<?php

namespace App\Http\Middleware;

use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;

class EnforceMonthlyQuota
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly UsageMeter $usageMeter,
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

        $quota = $this->entitlements->forApiKey($apiKey)['monthly_request_quota'];

        if ($this->usageMeter->currentMonthlyUsage($apiKey) >= $quota) {
            throw ApiException::quotaExceeded($quota, $this->usageMeter->monthlyResetAt());
        }

        $response = $next($request);
        $route = $request->route();
        $endpointCode = $route instanceof Route
            ? (string) ($route->defaults['ukapi_endpoint'] ?? 'unknown')
            : 'unknown';
        $usage = $response->getStatusCode() === 422
            ? $this->usageMeter->currentMonthlyUsage($apiKey)
            : $this->usageMeter->record($apiKey, $endpointCode);
        $response->headers->set('X-Quota-Limit', (string) $quota);
        $response->headers->set('X-Quota-Remaining', (string) max(0, $quota - $usage));
        $response->headers->set('X-Quota-Reset', (string) $this->usageMeter->monthlyResetAt());

        return $response;
    }
}

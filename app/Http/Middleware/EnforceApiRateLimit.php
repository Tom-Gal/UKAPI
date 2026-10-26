<?php

namespace App\Http\Middleware;

use App\Domain\Usage\Entitlements;
use App\Domain\Usage\UsageMeter;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiRateLimit
{
    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly UsageMeter $usageMeter,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('ukapi.api_key');

        if (! $apiKey instanceof ApiKey) {
            throw ApiException::invalidApiKey();
        }

        $plan = $request->attributes->get('ukapi.entitlements');
        $plan = is_array($plan) ? $plan : $this->entitlements->forApiKey($apiKey);
        $request->attributes->set('ukapi.entitlements', $plan);
        $limit = $plan['burst_requests_per_second'];
        $rateLimitKey = sprintf('ukapi:rate:%d:%s', $apiKey->id, now()->format('YmdHis'));

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit)) {
            try {
                $this->usageMeter->recordRejection($apiKey->user_id, 'rate_limit');
            } catch (\Throwable $exception) {
                report($exception);
            }

            throw ApiException::rateLimitExceeded(
                retryAfter: max(1, RateLimiter::availableIn($rateLimitKey)),
                limit: $limit,
            );
        }

        RateLimiter::hit($rateLimitKey, 1);
        $response = $next($request);
        $response->headers->set('RateLimit-Limit', (string) $limit);
        $response->headers->set('RateLimit-Remaining', (string) RateLimiter::remaining($rateLimitKey, $limit));
        $response->headers->set('RateLimit-Reset', (string) (now()->addSecond()->getTimestamp()));

        return $response;
    }
}

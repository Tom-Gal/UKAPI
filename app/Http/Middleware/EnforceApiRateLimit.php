<?php

namespace App\Http\Middleware;

use App\Domain\Usage\Entitlements;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiRateLimit
{
    public function __construct(private readonly Entitlements $entitlements) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('ukapi.api_key');

        if (! $apiKey instanceof ApiKey) {
            throw ApiException::invalidApiKey();
        }

        $limit = $this->entitlements->forApiKey($apiKey)['burst_requests_per_second'];
        $rateLimitKey = sprintf('ukapi:rate:%d:%s', $apiKey->id, now()->format('YmdHis'));

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit)) {
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

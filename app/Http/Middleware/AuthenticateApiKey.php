<?php

namespace App\Http\Middleware;

use App\Domain\ApiKeys\ApiKeyAuthenticator;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(private readonly ApiKeyAuthenticator $authenticator) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rateLimitKey = 'ukapi:auth-failure:'.hash('sha256', (string) $request->ip());
        $limit = max(1, (int) config('ukapi.api_auth_failure_limit'));
        $window = max(1, (int) config('ukapi.api_auth_failure_window_seconds'));

        if (RateLimiter::tooManyAttempts($rateLimitKey, $limit)) {
            throw ApiException::rateLimitExceeded(
                retryAfter: max(1, RateLimiter::availableIn($rateLimitKey)),
                limit: $limit,
            );
        }

        try {
            $apiKey = $this->authenticator->authenticate($request->bearerToken());
        } catch (ApiException $exception) {
            if (in_array($exception->apiCode, ['invalid_api_key', 'api_key_revoked'], true)) {
                RateLimiter::hit($rateLimitKey, $window);
            }

            throw $exception;
        }

        $request->attributes->set('ukapi.api_key', $apiKey);

        return $next($request);
    }
}

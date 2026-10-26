<?php

namespace App\Http\Middleware;

use App\Domain\ApiKeys\ApiKeyAuthenticator;
use App\Domain\ApiKeys\EndpointFamily;
use App\Domain\ApiKeys\IpAllowList;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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

        $endpoint = $request->route();
        $endpointCode = $endpoint instanceof Route
            ? (string) ($endpoint->defaults['ukapi_endpoint'] ?? 'unknown')
            : 'unknown';
        $family = EndpointFamily::forEndpoint($endpointCode);
        if ($apiKey->scopes !== null && ! in_array($family, $apiKey->scopes, true)) {
            throw ApiException::apiKeyScopeForbidden($family);
        }
        if (! IpAllowList::allows($request->ip(), $apiKey->ip_allow_list ?? [])) {
            throw ApiException::apiKeyIpNotAllowed();
        }

        $this->recordApproximateUse($apiKey, $family);
        $request->attributes->set('ukapi.api_key', $apiKey);

        return $next($request);
    }

    private function recordApproximateUse(ApiKey $apiKey, string $family): void
    {
        $usedAt = CarbonImmutable::now('UTC')->startOfHour();
        if ($apiKey->last_used_endpoint_family === $family && $apiKey->last_used_at?->equalTo($usedAt)) {
            return;
        }

        try {
            $apiKey->forceFill([
                'last_used_at' => $usedAt,
                'last_used_endpoint_family' => $family,
            ])->saveQuietly();
        } catch (\Throwable $exception) {
            // Activity metadata must not make an otherwise valid request fail.
            report($exception);
        }
    }
}

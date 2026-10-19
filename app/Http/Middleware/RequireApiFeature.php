<?php

namespace App\Http\Middleware;

use App\Domain\Usage\Entitlements;
use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireApiFeature
{
    public function __construct(private readonly Entitlements $entitlements) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $apiKey = $request->attributes->get('ukapi.api_key');

        if (! $apiKey instanceof ApiKey) {
            throw ApiException::invalidApiKey();
        }

        $plan = $request->attributes->get('ukapi.entitlements');
        $plan ??= $this->entitlements->forApiKey($apiKey);
        $request->attributes->set('ukapi.entitlements', $plan);

        if (! in_array($feature, $plan['features'] ?? [], true)) {
            throw ApiException::featureUnavailable($feature, (string) $plan['key']);
        }

        return $next($request);
    }
}

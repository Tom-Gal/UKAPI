<?php

namespace App\Http\Middleware;

use App\Domain\ApiKeys\ApiKeyAuthenticator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(private readonly ApiKeyAuthenticator $authenticator) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(
            'ukapi.api_key',
            $this->authenticator->authenticate($request->bearerToken()),
        );

        return $next($request);
    }
}

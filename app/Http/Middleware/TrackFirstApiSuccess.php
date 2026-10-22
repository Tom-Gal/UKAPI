<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackFirstApiSuccess
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $apiKey = $request->attributes->get('ukapi.api_key');

        if ($response->isSuccessful() && $apiKey instanceof ApiKey) {
            // A conditional write preserves the first success across keys and
            // concurrent requests, independently of expiring usage counters.
            try {
                User::query()
                    ->whereKey($apiKey->user_id)
                    ->whereNull('first_api_success_at')
                    ->update(['first_api_success_at' => now()]);
            } catch (\Throwable $exception) {
                // Onboarding analytics must never turn a successful API call
                // into an error after the response has already been produced.
                report($exception);
            }
        }

        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignApiRequestId
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id');

        if (! is_string($requestId) || ! preg_match('/^req_[A-Za-z0-9_-]{6,64}$/', $requestId)) {
            $requestId = 'req_'.Str::lower((string) Str::ulid());
        }

        $request->attributes->set('ukapi.request_id', $requestId);
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}

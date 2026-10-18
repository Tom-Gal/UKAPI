<?php

use App\Http\Middleware\AssignApiRequestId;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\TrustConfiguredProxies;
use App\Support\Api\ApiException;
use App\Support\Api\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(TrustConfiguredProxies::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
        $middleware->encryptCookies(except: ['sidebar_state']);
        // Cashier's production-default webhook route is /stripe/webhook. This
        // runs before configuration is loaded, so do not call config() here.
        $middleware->validateCsrfTokens(except: ['stripe/*']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetSecurityHeaders::class,
        ]);
        $middleware->api(prepend: [
            AssignApiRequestId::class,
        ]);
        $middleware->api(append: [
            SetSecurityHeaders::class,
        ]);
        $middleware->trustHosts();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('v1/*') || $request->expectsJson(),
        );
        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('v1/*')) {
                return null;
            }

            return ApiResponse::error(
                $request,
                code: $exception->apiCode,
                message: $exception->getMessage(),
                status: $exception->status,
                details: $exception->details,
                headers: $exception->headers,
            );
        });
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('v1/*')) {
                return null;
            }

            return ApiResponse::error(
                $request,
                code: 'invalid_parameter',
                message: 'One or more request parameters are invalid.',
                status: 422,
                details: ['fields' => $exception->errors()],
            );
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('v1/*')) {
                return null;
            }

            [$code, $message] = match ($exception->getStatusCode()) {
                400 => ['bad_request', 'The request could not be understood.'],
                401 => ['unauthorized', 'Authentication is required to access this API endpoint.'],
                403 => ['forbidden', 'You are not permitted to access this API endpoint.'],
                404 => ['not_found', 'The requested API endpoint was not found.'],
                405 => ['method_not_allowed', 'This HTTP method is not supported by the requested API endpoint.'],
                406 => ['not_acceptable', 'The requested response representation is not available.'],
                415 => ['unsupported_media_type', 'The request content type is not supported.'],
                default => ['http_error', 'The API request could not be completed.'],
            };

            return ApiResponse::error(
                $request,
                code: $code,
                message: $message,
                status: $exception->getStatusCode(),
            );
        });
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('v1/*')) {
                return null;
            }

            return ApiResponse::error(
                $request,
                code: 'internal_error',
                message: 'The API encountered an unexpected error. Please retry shortly.',
                status: 500,
            );
        });
    })->create();

<?php

use App\Http\Middleware\AssignApiRequestId;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->api(prepend: [
            AssignApiRequestId::class,
        ]);
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
            if (! $request->is('v1/*') || $exception->getStatusCode() !== 404) {
                return null;
            }

            return ApiResponse::error(
                $request,
                code: 'not_found',
                message: 'The requested API endpoint was not found.',
                status: 404,
            );
        });
    })->create();

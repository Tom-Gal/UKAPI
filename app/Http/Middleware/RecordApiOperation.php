<?php

namespace App\Http\Middleware;

use App\Domain\Operations\EndpointOperationRecorder;
use App\Support\Api\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class RecordApiOperation
{
    public function __construct(private readonly EndpointOperationRecorder $recorder) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $route = $request->route();
        $endpoint = $route instanceof Route
            ? (string) ($route->defaults['ukapi_endpoint'] ?? 'unknown')
            : 'unknown';

        try {
            $response = $next($request);
            $this->recorder->record($endpoint, $response->getStatusCode(), $this->elapsedMilliseconds($startedAt));

            return $response;
        } catch (\Throwable $exception) {
            $status = match (true) {
                $exception instanceof ApiException => $exception->status,
                $exception instanceof ValidationException => 422,
                $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
                default => 500,
            };
            $this->recorder->record($endpoint, $status, $this->elapsedMilliseconds($startedAt));

            throw $exception;
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) max(0, ceil((hrtime(true) - $startedAt) / 1_000_000));
    }
}

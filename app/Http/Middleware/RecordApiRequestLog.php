<?php

namespace App\Http\Middleware;

use App\Domain\Support\RequestLogRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RecordApiRequestLog
{
    public function __construct(private readonly RequestLogRecorder $recorder) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);

        try {
            $response = $next($request);
            $error = $this->recorder->errorFromResponse($response);
            $this->recorder->record(
                $request,
                $response->getStatusCode(),
                $this->elapsedMilliseconds($startedAt),
                $error,
                $this->recorder->cacheStateFromResponse($response),
            );

            return $response;
        } catch (Throwable $exception) {
            $error = $this->recorder->errorFromThrowable($exception);
            $this->recorder->record($request, $error['status'], $this->elapsedMilliseconds($startedAt), $error);

            throw $exception;
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) max(0, ceil((hrtime(true) - $startedAt) / 1_000_000));
    }
}

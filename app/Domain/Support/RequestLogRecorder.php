<?php

namespace App\Domain\Support;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use App\Support\Api\ApiException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class RequestLogRecorder
{
    /**
     * @return array{code: string|null, reason: string|null, message: string|null}
     */
    public function errorFromResponse(Response $response): array
    {
        if ($response->getStatusCode() < 400) {
            return ['code' => null, 'reason' => null, 'message' => null];
        }

        $content = $response->getContent();
        $payload = is_string($content) ? json_decode($content, true) : null;
        $error = is_array($payload) && is_array($payload['error'] ?? null) ? $payload['error'] : [];
        $code = is_string($error['code'] ?? null) ? $error['code'] : null;
        $message = is_string($error['message'] ?? null) ? $error['message'] : null;

        return [
            'code' => $code,
            'reason' => $this->reason($code, $response->getStatusCode()),
            'message' => $message,
        ];
    }

    /**
     * @return array{status: int, code: string, reason: string, message: string}
     */
    public function errorFromThrowable(Throwable $exception): array
    {
        if ($exception instanceof ApiException) {
            return [
                'status' => $exception->status,
                'code' => $exception->apiCode,
                'reason' => $this->reason($exception->apiCode, $exception->status) ?? 'api_error',
                'message' => $exception->getMessage(),
            ];
        }

        if ($exception instanceof ValidationException) {
            return [
                'status' => 422,
                'code' => 'invalid_parameter',
                'reason' => 'validation',
                'message' => 'One or more request parameters are invalid.',
            ];
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $code = match ($status) {
                400 => 'bad_request',
                401 => 'unauthorized',
                403 => 'forbidden',
                404 => 'not_found',
                405 => 'method_not_allowed',
                406 => 'not_acceptable',
                415 => 'unsupported_media_type',
                default => 'http_error',
            };

            return [
                'status' => $status,
                'code' => $code,
                'reason' => $this->reason($code, $status) ?? 'http_error',
                'message' => 'The API request could not be completed.',
            ];
        }

        return [
            'status' => 500,
            'code' => 'internal_error',
            'reason' => 'unexpected_application_error',
            'message' => 'The API encountered an unexpected error.',
        ];
    }

    /**
     * @param  array{code?: string|null, reason?: string|null, message?: string|null}  $error
     */
    public function record(Request $request, int $status, int $durationMs, array $error): void
    {
        try {
            $route = $request->route();
            $apiKey = $request->attributes->get('ukapi.api_key');
            $apiKey = $apiKey instanceof ApiKey ? $apiKey : null;

            ApiRequestLog::query()->create([
                'request_id' => (string) $request->attributes->get('ukapi.request_id'),
                'method' => strtoupper($request->method()),
                'route_path' => $route instanceof Route ? $route->uri() : null,
                'route_name' => $route instanceof Route ? $route->getName() : null,
                'endpoint' => $route instanceof Route
                    ? (string) ($route->defaults['ukapi_endpoint'] ?? 'unknown')
                    : 'unknown',
                'status' => max(100, min(599, $status)),
                'duration_ms' => max(0, min(600_000, $durationMs)),
                'error_code' => $error['code'] ?? null,
                'error_reason' => $error['reason'] ?? null,
                'error_message' => $error['message'] ?? null,
                'user_id' => $apiKey?->user_id,
                'api_key_id' => $apiKey?->id,
            ]);
        } catch (Throwable $exception) {
            // Support telemetry must never change the API response or mask the original failure.
            report($exception);
        }
    }

    private function reason(?string $code, int $status): ?string
    {
        if ($status < 400) {
            return null;
        }

        return match (true) {
            in_array($code, ['invalid_api_key', 'api_key_revoked', 'api_key_scope_forbidden', 'api_key_ip_not_allowed'], true) => 'authentication_or_authorisation',
            in_array($code, ['invalid_parameter', 'invalid_postcode', 'invalid_outcode', 'invalid_coordinates', 'invalid_company_number', 'invalid_sic_code', 'invalid_food_establishment_id'], true) => 'validation',
            in_array($code, ['rate_limit_exceeded', 'quota_exceeded'], true) => 'access_limit',
            in_array($code, ['upstream_unavailable', 'upstream_authentication_failed', 'upstream_invalid_response'], true) => 'upstream_provider',
            $code === 'usage_unavailable' => 'usage_accounting',
            $code === 'feature_not_available' => 'plan_entitlement',
            $status >= 500 => 'server_error',
            default => 'client_error',
        };
    }
}

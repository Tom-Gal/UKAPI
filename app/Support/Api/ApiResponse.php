<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(Request $request, mixed $data, array $meta = []): JsonResponse
    {
        $requestId = self::requestId($request);

        return response()->json([
            'data' => $data,
            'meta' => [
                ...$meta,
                'request_id' => $requestId,
            ],
        ])->header('X-Request-Id', $requestId);
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    public static function error(
        Request $request,
        string $code,
        string $message,
        int $status,
        array $details = [],
        array $headers = [],
    ): JsonResponse {
        $requestId = self::requestId($request);
        $error = [
            'code' => $code,
            'message' => $message,
            'status' => $status,
            'request_id' => $requestId,
        ];

        if ($details !== []) {
            $error['details'] = $details;
        }

        return response()
            ->json(['error' => $error], $status)
            ->withHeaders([...$headers, 'X-Request-Id' => $requestId]);
    }

    private static function requestId(Request $request): string
    {
        return (string) $request->attributes->get(
            'ukapi.request_id',
            'req_'.Str::lower((string) Str::ulid()),
        );
    }
}

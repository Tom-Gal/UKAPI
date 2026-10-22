<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MotRegistrationRequest;
use App\Http\Requests\Api\V1\MotVinRequest;
use App\Integrations\Contracts\MotHistoryProvider;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class MotHistoryController extends Controller
{
    public function registration(MotRegistrationRequest $request, MotHistoryProvider $provider): JsonResponse
    {
        return ApiResponse::success($request, $provider->byRegistration($request->registration()), $this->meta());
    }

    public function vin(MotVinRequest $request, MotHistoryProvider $provider): JsonResponse
    {
        return ApiResponse::success($request, $provider->byVin($request->vin()), $this->meta());
    }

    /** @return array<string, string> */
    private function meta(): array
    {
        return ['source' => 'mot_history', 'coverage' => 'GB and Northern Ireland'];
    }
}

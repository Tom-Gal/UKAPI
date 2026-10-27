<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Mot\MotHistoryLookup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MotRegistrationRequest;
use App\Http\Requests\Api\V1\MotVinRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class MotHistoryController extends Controller
{
    public function registration(MotRegistrationRequest $request, MotHistoryLookup $lookup): JsonResponse
    {
        $result = $lookup->byRegistration($request->registration());

        return ApiResponse::success($request, $result->vehicle->toArray(), $this->meta($result->cached));
    }

    public function vin(MotVinRequest $request, MotHistoryLookup $lookup): JsonResponse
    {
        $result = $lookup->byVin($request->vin());

        return ApiResponse::success($request, $result->vehicle->toArray(), $this->meta($result->cached));
    }

    /** @return array<string, bool|string|null> */
    private function meta(bool $cached): array
    {
        return [
            'cached' => $cached,
            'source' => 'mot_history',
            'source_updated_at' => null,
            'coverage' => 'Great Britain and Northern Ireland',
        ];
    }
}

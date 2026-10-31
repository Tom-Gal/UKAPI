<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Geography\GeographyService;
use App\Domain\Geography\UkCouncilCode;
use App\Domain\Geography\UkPostcode;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CouncilController extends Controller
{
    public function postcode(Request $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $result = $geography->councilForPostcode(UkPostcode::from($postcode));

        return ApiResponse::success($request, [
            'postcode' => $result['postcode']->postcode,
            'council' => $result['council']->toArray(),
        ], [
            'cached' => $result['cached'],
            'source' => 'postcodes_io+ons_lad_2024',
            'source_updated_at' => null,
            'coverage' => 'UK',
        ]);
    }

    public function show(Request $request, string $code, GeographyService $geography): JsonResponse
    {
        $council = $geography->council(UkCouncilCode::from($code));

        return ApiResponse::success($request, $council->toArray(), [
            'cached' => false,
            'source' => 'ons_lad_2024',
            'source_updated_at' => null,
            'coverage' => 'UK',
        ]);
    }
}

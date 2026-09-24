<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Geography\Coordinates;
use App\Domain\Geography\GeographyCollection;
use App\Domain\Geography\GeographyLookup;
use App\Domain\Geography\GeographyService;
use App\Domain\Geography\GeographyValidation;
use App\Domain\Geography\UkPostcode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NearbyPostcodesRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostcodeController extends Controller
{
    public function show(Request $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $result = $geography->lookup(UkPostcode::from($postcode));

        return ApiResponse::success($request, $result->postcode->toArray(), $this->meta($result));
    }

    public function validate(Request $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $normalised = UkPostcode::from($postcode);
        $result = $geography->validate($normalised);

        return ApiResponse::success($request, [
            'postcode' => $normalised->display(),
            'valid' => $result->valid,
        ], $this->meta($result));
    }

    public function nearby(NearbyPostcodesRequest $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $normalised = UkPostcode::from($postcode);
        $result = $geography->nearby($normalised, $request->limit(), $request->radiusMetres());

        return ApiResponse::success($request, [
            'postcode' => $normalised->display(),
            'results' => array_map(
                static fn ($record): array => $record->toArray(),
                $result->postcodes,
            ),
        ], $this->meta($result));
    }

    public function reverse(Request $request, string $latitude, string $longitude, GeographyService $geography): JsonResponse
    {
        $result = $geography->reverse(Coordinates::from($latitude, $longitude));

        return ApiResponse::success($request, $result->postcode->toArray(), $this->meta($result));
    }

    /** @return array<string, bool|string|null> */
    private function meta(GeographyLookup|GeographyCollection|GeographyValidation $result): array
    {
        return [
            'cached' => $result->cached,
            'source' => 'postcodes_io',
            'source_updated_at' => null,
            'coverage' => 'UK',
        ];
    }
}

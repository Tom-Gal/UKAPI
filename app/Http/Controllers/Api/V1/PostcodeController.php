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
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/**
 * @group Postcodes & location
 *
 * Canonical UK postcode, nearby-postcode and coordinate lookup data from Postcodes.io.
 */
#[Response(content: [
    'error' => [
        'code' => 'invalid_api_key',
        'message' => 'The API key is invalid.',
        'status' => 401,
        'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd',
    ],
], status: 401, description: 'Missing, invalid or revoked API key')]
#[Response(content: [
    'error' => [
        'code' => 'rate_limit_exceeded',
        'message' => 'Too many requests. Please retry shortly.',
        'status' => 429,
        'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd',
    ],
], status: 429, description: 'Burst limit or monthly quota reached')]
final class PostcodeController extends Controller
{
    /**
     * Look up a postcode.
     *
     * Returns a normalised UK postcode and selected geographic references.
     */
    #[UrlParam('postcode', 'string', 'UK postcode to look up. Spacing and case are normalised.', example: 'BL2 6XX')]
    #[Response(content: [
        'data' => [
            'postcode' => 'BL2 6XX',
            'country' => 'England',
            'region' => 'North West',
            'latitude' => 53.5922,
            'longitude' => -2.4117,
            'local_authority' => ['name' => 'Bolton', 'code' => 'E08000001'],
        ],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'postcodes_io', 'coverage' => 'UK'],
    ], description: 'Postcode found')]
    public function show(Request $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $result = $geography->lookup(UkPostcode::from($postcode));

        return ApiResponse::success($request, $result->postcode->toArray(), $this->meta($result));
    }

    /**
     * Validate a postcode.
     *
     * Checks whether a normalised UK postcode has a provider record.
     */
    #[UrlParam('postcode', 'string', 'UK postcode to validate. Spacing and case are normalised.', example: 'BL2 6XX')]
    #[Response(content: [
        'data' => ['postcode' => 'BL2 6XX', 'valid' => true],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => true, 'source' => 'postcodes_io', 'coverage' => 'UK'],
    ], description: 'Postcode validation result')]
    public function validate(Request $request, string $postcode, GeographyService $geography): JsonResponse
    {
        $normalised = UkPostcode::from($postcode);
        $result = $geography->validate($normalised);

        return ApiResponse::success($request, [
            'postcode' => $normalised->display(),
            'valid' => $result->valid,
        ], $this->meta($result));
    }

    /**
     * Find nearby postcodes.
     *
     * Results are ordered by distance in metres.
     */
    #[UrlParam('postcode', 'string', 'UK postcode used as the search origin.', example: 'BL2 6XX')]
    #[Response(content: [
        'data' => [
            'postcode' => 'BL2 6XX',
            'results' => [['postcode' => 'BL2 6XY', 'distance_metres' => 152.4, 'latitude' => 53.5931, 'longitude' => -2.4108]],
        ],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'postcodes_io', 'coverage' => 'UK'],
    ], description: 'Nearby postcodes')]
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

    /**
     * Reverse geocode coordinates.
     *
     * Returns the nearest UK postcode for WGS84 coordinates.
     */
    #[UrlParam('latitude', 'number', 'WGS84 latitude from -90 to 90.', example: 53.5922)]
    #[UrlParam('longitude', 'number', 'WGS84 longitude from -180 to 180.', example: -2.4117)]
    #[Response(content: [
        'data' => ['postcode' => 'BL2 6XX', 'country' => 'England', 'latitude' => 53.5922, 'longitude' => -2.4117],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'postcodes_io', 'coverage' => 'UK'],
    ], description: 'Nearest postcode')]
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

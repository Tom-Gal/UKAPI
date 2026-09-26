<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Flood\FloodReadings;
use App\Domain\Flood\FloodService;
use App\Domain\Flood\FloodStationReference;
use App\Domain\Flood\FloodStations;
use App\Domain\Flood\FloodWarnings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FloodPostcodeRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/**
 * @group Flood monitoring
 *
 * Near-real-time England flood warnings, stations and readings from the Environment Agency. This data is not an emergency alert service.
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
        'code' => 'upstream_unavailable',
        'message' => 'The upstream data provider is temporarily unavailable. Please retry shortly.',
        'status' => 503,
        'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd',
    ],
], status: 503, description: 'Environment Agency data is temporarily unavailable')]
final class FloodController extends Controller
{
    /**
     * List current flood warnings.
     *
     * Lists current England flood warnings and alerts. This endpoint is not an emergency alert service.
     */
    #[Response(content: [
        'data' => ['warnings' => [[
            'id' => '061WAFSF3A', 'severity' => 2, 'severity_level' => 'Flood Warning', 'type' => 'Flood Warning',
            'message' => 'Flooding is possible in low lying areas.', 'raised_at' => '2026-09-25T09:00:00+00:00',
            'area' => ['code' => '061WAFSF3A', 'county' => 'Somerset', 'river_or_sea' => 'River Tone'],
        ]]],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'environment_agency_flood_monitoring', 'retrieved_at' => '2026-09-25T10:20:00+00:00',
            'source_updated_at' => '2026-09-25T10:15:00+00:00', 'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ],
    ], description: 'Current flood warnings')]
    public function warnings(Request $request, FloodService $flood): JsonResponse
    {
        $result = $flood->warnings();

        return ApiResponse::success($request, [
            'warnings' => array_map(static fn ($warning): array => $warning->toArray(), $result->warnings),
        ], $this->meta($result));
    }

    /**
     * Find flood warnings near a postcode.
     *
     * Finds current England flood warnings and alerts near a postcode.
     */
    #[Response(content: [
        'data' => ['postcode' => 'BL2 6XX', 'nearby_distance_kilometres' => 10, 'warnings' => []],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'environment_agency_flood_monitoring', 'retrieved_at' => '2026-09-25T10:20:00+00:00',
            'source_updated_at' => '2026-09-25T10:15:00+00:00', 'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ],
    ], description: 'Nearby flood warnings')]
    public function nearby(FloodPostcodeRequest $request, FloodService $flood): JsonResponse
    {
        $postcode = $request->postcode();
        $result = $flood->warningsNear($postcode);

        return ApiResponse::success($request, [
            'postcode' => $postcode->display(),
            'nearby_distance_kilometres' => $flood->nearbyDistanceKilometres(),
            'warnings' => array_map(static fn ($warning): array => $warning->toArray(), $result->warnings),
        ], $this->meta($result));
    }

    /**
     * Find flood stations near a postcode.
     *
     * Finds Environment Agency monitoring stations near a postcode.
     */
    #[Response(content: [
        'data' => [
            'postcode' => 'BL2 6XX', 'nearby_distance_kilometres' => 10,
            'stations' => [[
                'id' => '5380TH', 'name' => 'Walthamstow, Low Hall', 'latitude' => 51.574894, 'longitude' => -0.043637,
                'river_name' => 'River Lee', 'town' => 'Walthamstow', 'status' => 'statusActive',
                'measures' => [['parameter' => 'level', 'parameter_name' => 'Water Level', 'unit' => 'mASD', 'period_seconds' => 900]],
            ]],
        ],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'environment_agency_flood_monitoring', 'retrieved_at' => '2026-09-25T10:20:00+00:00',
            'source_updated_at' => null, 'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ],
    ], description: 'Nearby flood-monitoring stations')]
    public function stationsNearby(FloodPostcodeRequest $request, FloodService $flood): JsonResponse
    {
        $postcode = $request->postcode();
        $result = $flood->stationsNear($postcode);

        return ApiResponse::success($request, [
            'postcode' => $postcode->display(),
            'nearby_distance_kilometres' => $flood->nearbyDistanceKilometres(),
            'stations' => array_map(static fn ($station): array => $station->toArray(), $result->stations),
        ], $this->meta($result));
    }

    /**
     * List flood station readings.
     *
     * Lists a bounded set of recent readings for an Environment Agency station.
     */
    #[UrlParam('station', 'string', 'Environment Agency station identifier. Letters, numbers, hyphens and underscores are accepted.', example: '5380TH')]
    #[Response(content: [
        'data' => ['station_id' => '5380TH', 'readings' => [['measure_id' => '5380TH-level-stage-i-15_min-mASD', 'recorded_at' => '2026-08-27T00:00:00+00:00', 'value' => 0.027]]],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'environment_agency_flood_monitoring', 'retrieved_at' => '2026-09-25T10:20:00+00:00',
            'source_updated_at' => '2026-08-27T00:00:00+00:00', 'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ],
    ], description: 'Recent station readings')]
    public function readings(Request $request, string $station, FloodService $flood): JsonResponse
    {
        $reference = FloodStationReference::from($station);
        $result = $flood->readings($reference);

        return ApiResponse::success($request, [
            'station_id' => $reference->value(),
            'readings' => array_map(static fn ($reading): array => $reading->toArray(), $result->readings),
        ], $this->meta($result));
    }

    /** @return array<string, bool|string|null> */
    private function meta(FloodWarnings|FloodStations|FloodReadings $result): array
    {
        return [
            'cached' => $result->cached,
            'source' => 'environment_agency_flood_monitoring',
            'retrieved_at' => $result->retrievedAt,
            'source_updated_at' => $result instanceof FloodStations ? null : $result->sourceUpdatedAt,
            'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ];
    }
}

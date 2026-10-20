<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Flood\FloodReadings;
use App\Domain\Flood\FloodService;
use App\Domain\Flood\FloodStationReference;
use App\Domain\Flood\FloodStations;
use App\Domain\Flood\FloodWarnings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FloodChangesRequest;
use App\Http\Requests\Api\V1\FloodPostcodeRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Flood monitoring
 *
 * Near-real-time England flood warnings, stations and readings from the Environment Agency. This data is not an emergency alert service.
 */
final class FloodController extends Controller
{
    /**
     * List current flood warnings.
     *
     * Lists current England flood warnings and alerts. This endpoint is not an emergency alert service.
     */
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
    public function readings(Request $request, string $id, FloodService $flood): JsonResponse
    {
        $reference = FloodStationReference::from($id);
        $result = $flood->readings($reference);

        return ApiResponse::success($request, [
            'station_id' => $reference->value(),
            'readings' => array_map(static fn ($reading): array => $reading->toArray(), $result->readings),
        ], $this->meta($result));
    }

    public function changes(FloodChangesRequest $request, string $id, FloodService $flood): JsonResponse
    {
        $station = FloodStationReference::from($id);
        $changes = $flood->readingChanges($station, $request->since(), $request->until());
        $from = $changes['from'];
        $to = $changes['to'];

        return ApiResponse::success($request, [
            'station_id' => $station->value(),
            'since' => $changes['since'],
            'until' => $changes['until'],
            'added' => $changes['diff']['added'],
            'removed' => $changes['diff']['removed'],
            'changed' => $changes['diff']['changed'],
        ], [
            'cached' => true,
            'source' => 'environment_agency_flood_monitoring',
            'source_updated_at' => $to->source_updated_at?->toISOString(),
            'coverage' => 'England',
            'safety_notice' => 'This data is an archive of monitoring readings, not an emergency alert service. Follow official Environment Agency advice and warnings.',
            'from_snapshot' => [
                'version' => $from->version,
                'checksum' => $from->checksum,
                'captured_at' => $from->captured_at?->toISOString(),
            ],
            'to_snapshot' => [
                'version' => $to->version,
                'checksum' => $to->checksum,
                'captured_at' => $to->captured_at?->toISOString(),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function meta(FloodWarnings|FloodStations|FloodReadings $result): array
    {
        $meta = [
            'cached' => $result->cached,
            'source' => 'environment_agency_flood_monitoring',
            'retrieved_at' => $result->retrievedAt,
            'source_updated_at' => $result instanceof FloodStations ? null : $result->sourceUpdatedAt,
            'coverage' => 'England',
            'safety_notice' => 'This data is not an emergency alert service. Follow official Environment Agency advice and warnings.',
        ];

        if ($result instanceof FloodReadings && $result->snapshotVersion !== null) {
            $meta['snapshot'] = [
                'version' => $result->snapshotVersion,
                'checksum' => $result->snapshotChecksum,
                'captured_at' => $result->snapshotCapturedAt,
            ];
        }

        return $meta;
    }
}

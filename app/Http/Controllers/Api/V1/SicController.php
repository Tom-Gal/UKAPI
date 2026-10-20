<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Companies\SicCode;
use App\Domain\Companies\SicData;
use App\Domain\Companies\SicLookup;
use App\Domain\Companies\SicSnapshot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SicChangesRequest;
use App\Http\Requests\Api\V1\SicSearchRequest;
use App\Support\Api\ApiResponse;
use App\Support\Api\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Companies & SIC
 *
 * Search and retrieve records from the locally refreshed Companies House condensed SIC 2007 snapshot.
 */
final class SicController extends Controller
{
    /**
     * Look up a SIC code.
     *
     * Retrieves a five-digit Companies House condensed SIC 2007 code.
     */
    public function show(Request $request, string $code, SicLookup $sic): JsonResponse
    {
        $result = $sic->find(SicCode::from($code));

        return ApiResponse::success(
            $request,
            $result['sic']->toArray(),
            $this->meta($result['snapshot'], $result['cached'], $result['snapshot_version']),
        );
    }

    /**
     * Search SIC codes.
     *
     * Searches the SIC reference by code or description.
     */
    public function search(SicSearchRequest $request, SicLookup $sic): JsonResponse
    {
        $page = $request->pageNumber();
        $perPage = $request->perPage();
        $result = $sic->search($request->queryText(), $page, $perPage);

        return ApiResponse::success(
            $request,
            array_map(static fn (SicData $record): array => $record->toArray(), $result['records']),
            [
                ...$this->meta($result['snapshot'], $result['cached'], $result['snapshot_version']),
                'pagination' => Pagination::from($page, $perPage, $result['total'])->toArray(),
            ],
        );
    }

    public function changes(SicChangesRequest $request, SicLookup $sic): JsonResponse
    {
        $changes = $sic->changes($request->since(), $request->until());
        $from = $changes['from'];
        $to = $changes['to'];

        return ApiResponse::success($request, [
            'since' => $changes['since'],
            'until' => $changes['until'],
            'added' => $changes['diff']['added'],
            'removed' => $changes['diff']['removed'],
            'changed' => $changes['diff']['changed'],
        ], [
            'cached' => true,
            'source' => 'companies_house_sic_2007',
            'source_updated_at' => $to->source_updated_at?->toISOString(),
            'coverage' => 'UK',
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
    private function meta(SicSnapshot $snapshot, bool $cached, ?string $snapshotVersion = null): array
    {
        return [
            'cached' => $cached,
            'source' => 'companies_house_sic_2007',
            'source_updated_at' => $snapshot->retrievedAt,
            'coverage' => 'UK',
            'reference_version' => $snapshot->version,
            'snapshot_version' => $snapshotVersion,
        ];
    }
}

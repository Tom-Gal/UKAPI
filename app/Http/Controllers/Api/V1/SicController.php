<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Companies\SicCode;
use App\Domain\Companies\SicData;
use App\Domain\Companies\SicLookup;
use App\Domain\Companies\SicSnapshot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SicSearchRequest;
use App\Support\Api\ApiResponse;
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
            $this->meta($result['snapshot'], $result['cached']),
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
                ...$this->meta($result['snapshot'], $result['cached']),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $result['total'],
                ],
            ],
        );
    }

    /** @return array{cached: bool, source: string, source_updated_at: string, coverage: string, reference_version: string} */
    private function meta(SicSnapshot $snapshot, bool $cached): array
    {
        return [
            'cached' => $cached,
            'source' => 'companies_house_sic_2007',
            'source_updated_at' => $snapshot->retrievedAt,
            'coverage' => 'UK',
            'reference_version' => $snapshot->version,
        ];
    }
}

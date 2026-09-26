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
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/**
 * @group Companies & SIC
 *
 * Search and retrieve records from the locally refreshed Companies House condensed SIC 2007 snapshot.
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
        'code' => 'sic_reference_unavailable',
        'message' => 'The SIC reference snapshot is temporarily unavailable. Please retry shortly.',
        'status' => 503,
        'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd',
    ],
], status: 503, description: 'The SIC snapshot is temporarily unavailable')]
final class SicController extends Controller
{
    /**
     * Look up a SIC code.
     *
     * Retrieves a five-digit Companies House condensed SIC 2007 code.
     */
    #[UrlParam('code', 'string', 'Five-digit Companies House condensed SIC 2007 code. Whitespace is removed.', example: '62012')]
    #[Response(content: [
        'data' => ['code' => '62012', 'description' => 'Business and domestic software development'],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'companies_house_sic_2007', 'source_updated_at' => '2026-09-24T12:00:00+00:00',
            'coverage' => 'UK', 'reference_version' => 'companies_house_condensed_sic_2007',
        ],
    ], description: 'SIC record')]
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
    #[Response(content: [
        'data' => [['code' => '62012', 'description' => 'Business and domestic software development']],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false,
            'source' => 'companies_house_sic_2007', 'source_updated_at' => '2026-09-24T12:00:00+00:00',
            'coverage' => 'UK', 'reference_version' => 'companies_house_condensed_sic_2007',
            'pagination' => ['page' => 1, 'per_page' => 25, 'total' => 18],
        ],
    ], description: 'Paginated SIC results')]
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

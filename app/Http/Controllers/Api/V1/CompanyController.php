<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Companies\CompanyFilings;
use App\Domain\Companies\CompanyLookup;
use App\Domain\Companies\CompanyNumber;
use App\Domain\Companies\CompanyOfficers;
use App\Domain\Companies\CompanyProfile;
use App\Domain\Companies\CompanySearchResults;
use App\Domain\Companies\CompanySummary;
use App\Domain\Companies\FilingData;
use App\Domain\Companies\OfficerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompanyCollectionRequest;
use App\Http\Requests\Api\V1\CompanySearchRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

/**
 * @group Companies & SIC
 *
 * Simplified Companies House profiles, officers and filing metadata with predictable pagination.
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
], status: 503, description: 'Companies House is temporarily unavailable')]
final class CompanyController extends Controller
{
    /**
     * Look up a company.
     *
     * Returns a simplified Companies House company profile.
     */
    #[UrlParam('companyNumber', 'string', 'Companies House company number. Whitespace is removed and letters are uppercased.', example: 'SC012345')]
    #[Response(content: [
        'data' => [
            'company_number' => 'SC012345',
            'name' => 'Example Technology Ltd',
            'status' => 'active',
            'type' => 'ltd',
            'jurisdiction' => 'scotland',
            'incorporated_on' => '2019-04-12',
            'sic_codes' => ['62012', '63110'],
        ],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'stale' => false, 'source' => 'companies_house', 'coverage' => 'UK'],
    ], description: 'Company profile')]
    public function show(Request $request, string $companyNumber, CompanyLookup $companies): JsonResponse
    {
        $result = $companies->find(CompanyNumber::from($companyNumber));

        return ApiResponse::success($request, $result->company->toArray(), $this->meta($result));
    }

    /**
     * Search companies.
     *
     * Searches Companies House company records by name.
     */
    #[Response(content: [
        'data' => [['company_number' => 'SC012345', 'name' => 'Example Technology Ltd', 'status' => 'active', 'type' => 'ltd']],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'stale' => false, 'source' => 'companies_house', 'coverage' => 'UK',
            'pagination' => ['page' => 1, 'per_page' => 25, 'total' => 42],
        ],
    ], description: 'Paginated company search results')]
    public function search(CompanySearchRequest $request, CompanyLookup $companies): JsonResponse
    {
        $page = $request->pageNumber();
        $perPage = $request->perPage();
        $result = $companies->search($request->queryText(), $page, $perPage);

        return ApiResponse::success(
            $request,
            array_map(static fn (CompanySummary $company): array => $company->toArray(), $result->companies),
            $this->meta($result, $page, $perPage),
        );
    }

    /**
     * List company officers.
     *
     * Returns a company's officers without date-of-birth data.
     */
    #[UrlParam('companyNumber', 'string', 'Companies House company number whose officers are requested.', example: 'SC012345')]
    #[Response(content: [
        'data' => [['name' => 'Alex Example', 'role' => 'director', 'appointed_on' => '2020-01-15', 'nationality' => 'British']],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'stale' => false, 'source' => 'companies_house', 'coverage' => 'UK',
            'pagination' => ['page' => 1, 'per_page' => 25, 'total' => 2],
        ],
    ], description: 'Paginated company officers')]
    public function officers(
        CompanyCollectionRequest $request,
        string $companyNumber,
        CompanyLookup $companies,
    ): JsonResponse {
        $page = $request->pageNumber();
        $perPage = $request->perPage();
        $result = $companies->officers(CompanyNumber::from($companyNumber), $page, $perPage);

        return ApiResponse::success(
            $request,
            array_map(static fn (OfficerData $officer): array => $officer->toArray(), $result->officers),
            $this->meta($result, $page, $perPage),
        );
    }

    /**
     * List company filings.
     *
     * Returns filing metadata; documents, links and contents are intentionally not proxied.
     */
    #[UrlParam('companyNumber', 'string', 'Companies House company number whose filing history is requested.', example: 'SC012345')]
    #[Response(content: [
        'data' => [[
            'transaction_id' => 'MzAwMDAwMDAwMGFkaXF6a2N4', 'category' => 'accounts', 'type' => 'AA',
            'filed_on' => '2025-03-31', 'description' => 'accounts-with-accounts-type-full', 'pages' => 12,
        ]],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'stale' => false, 'source' => 'companies_house', 'coverage' => 'UK',
            'pagination' => ['page' => 1, 'per_page' => 25, 'total' => 42],
        ],
    ], description: 'Paginated company filing metadata')]
    public function filings(
        CompanyCollectionRequest $request,
        string $companyNumber,
        CompanyLookup $companies,
    ): JsonResponse {
        $page = $request->pageNumber();
        $perPage = $request->perPage();
        $result = $companies->filings(CompanyNumber::from($companyNumber), $page, $perPage);

        return ApiResponse::success(
            $request,
            array_map(static fn (FilingData $filing): array => $filing->toArray(), $result->filings),
            $this->meta($result, $page, $perPage),
        );
    }

    /** @return array<string, bool|int|string|null|array<string, int>> */
    private function meta(
        CompanyProfile|CompanySearchResults|CompanyOfficers|CompanyFilings $result,
        ?int $page = null,
        ?int $perPage = null,
    ): array {
        $meta = [
            'cached' => $result->cached,
            'stale' => $result->stale,
            'source' => 'companies_house',
            'source_updated_at' => null,
            'coverage' => 'UK',
        ];

        if ($page !== null && $perPage !== null && ($result instanceof CompanySearchResults || $result instanceof CompanyOfficers || $result instanceof CompanyFilings)) {
            $meta['pagination'] = [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result->total,
            ];
        }

        return $meta;
    }
}

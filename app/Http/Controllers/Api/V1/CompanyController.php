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

final class CompanyController extends Controller
{
    public function show(Request $request, string $companyNumber, CompanyLookup $companies): JsonResponse
    {
        $result = $companies->find(CompanyNumber::from($companyNumber));

        return ApiResponse::success($request, $result->company->toArray(), $this->meta($result));
    }

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

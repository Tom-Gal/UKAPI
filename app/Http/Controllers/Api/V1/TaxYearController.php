<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tax\TaxYear;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TaxYearController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        return $this->response($request, TaxYear::current());
    }

    public function show(Request $request, string $year): JsonResponse
    {
        return $this->response($request, TaxYear::fromYear($year));
    }

    private function response(Request $request, TaxYear $taxYear): JsonResponse
    {
        return ApiResponse::success($request, $taxYear->toArray(), [
            'cached' => false,
            'source' => 'ukapi',
            'source_updated_at' => null,
            'coverage' => 'UK',
            'disclaimer' => 'Tax-year dates are provided as a calendar reference and are not tax advice.',
        ]);
    }
}

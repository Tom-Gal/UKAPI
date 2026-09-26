<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tax\VatCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VatRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @group VAT
 *
 * Deterministic VAT calculations for GBP amounts. Calculations are provided for convenience and are not tax advice.
 */
class VatController extends Controller
{
    /**
     * Calculate VAT.
     *
     * Calculates the VAT to add to a net GBP amount.
     */
    public function calculate(VatRequest $request, VatCalculator $vatCalculator): JsonResponse
    {
        return ApiResponse::success(
            $request,
            $vatCalculator->calculate($request->amount(), $request->rate()),
            $this->meta(),
        );
    }

    /**
     * Remove VAT.
     *
     * Removes VAT from a gross GBP amount.
     */
    public function remove(VatRequest $request, VatCalculator $vatCalculator): JsonResponse
    {
        return ApiResponse::success(
            $request,
            $vatCalculator->remove($request->amount(), $request->rate()),
            $this->meta(),
        );
    }

    /** @return array<string, bool|string> */
    private function meta(): array
    {
        return [
            'cached' => false,
            'source' => 'ukapi',
            'coverage' => 'UK',
            'disclaimer' => 'VAT calculations are provided for convenience and are not tax advice.',
        ];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tax\VatCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\VatRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Knuckles\Scribe\Attributes\Response;

/**
 * @group VAT
 *
 * Deterministic VAT calculations for GBP amounts. Calculations are provided for convenience and are not tax advice.
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
        'code' => 'invalid_parameter',
        'message' => 'One or more request parameters are invalid.',
        'status' => 422,
        'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd',
        'details' => ['fields' => ['amount' => ['The amount format is invalid.']]],
    ],
], status: 422, description: 'Invalid amount or rate')]
class VatController extends Controller
{
    /**
     * Calculate VAT.
     *
     * Calculates the VAT to add to a net GBP amount.
     */
    #[Response(content: [
        'data' => ['amount' => '100.00', 'rate_percent' => '20.00', 'vat_amount' => '20.00', 'total_amount' => '120.00', 'currency' => 'GBP'],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'ukapi', 'coverage' => 'UK'],
    ], description: 'VAT calculation')]
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
    #[Response(content: [
        'data' => ['gross_amount' => '120.00', 'rate_percent' => '20.00', 'vat_amount' => '20.00', 'net_amount' => '100.00', 'currency' => 'GBP'],
        'meta' => ['request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'ukapi', 'coverage' => 'UK'],
    ], description: 'VAT removal calculation')]
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

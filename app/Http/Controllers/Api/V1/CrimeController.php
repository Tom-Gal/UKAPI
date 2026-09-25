<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Crime\CrimeCategories;
use App\Domain\Crime\CrimeResults;
use App\Domain\Crime\CrimeService;
use App\Domain\Crime\YearMonth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CrimeCategoriesRequest;
use App\Http\Requests\Api\V1\CrimePostcodeRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CrimeController extends Controller
{
    public function nearby(CrimePostcodeRequest $request, CrimeService $crime): JsonResponse
    {
        $postcode = $request->postcode();
        $month = $request->month();
        $result = $crime->nearby($postcode, $month);

        return ApiResponse::success($request, [
            'postcode' => $postcode->display(),
            'crimes' => array_map(static fn ($record): array => $record->toArray(), $result->crimes),
        ], $this->meta($result, $month));
    }

    public function summary(CrimePostcodeRequest $request, CrimeService $crime): JsonResponse
    {
        $postcode = $request->postcode();
        $month = $request->month();
        $result = $crime->nearby($postcode, $month);
        $counts = [];

        foreach ($result->crimes as $record) {
            $counts[$record->category] = ($counts[$record->category] ?? 0) + 1;
        }

        ksort($counts);

        return ApiResponse::success($request, [
            'postcode' => $postcode->display(),
            'month' => $month?->value() ?? $this->sourceMonth($result),
            'total' => count($result->crimes),
            'by_category' => array_map(
                static fn (string $category, int $count): array => ['category' => $category, 'count' => $count],
                array_keys($counts),
                array_values($counts),
            ),
        ], $this->meta($result, $month));
    }

    public function categories(CrimeCategoriesRequest $request, CrimeService $crime): JsonResponse
    {
        $month = $request->month();
        $result = $crime->categories($month);

        return ApiResponse::success($request, [
            'categories' => array_map(static fn ($category): array => $category->toArray(), $result->categories),
        ], $this->meta($result, $month));
    }

    /** @return array<string, bool|string|null> */
    private function meta(CrimeResults|CrimeCategories $result, ?YearMonth $month): array
    {
        return [
            'cached' => $result->cached,
            'source' => 'police_uk',
            'source_updated_at' => null,
            'coverage' => 'England, Wales and Northern Ireland; Scotland has British Transport Police data only.',
            'requested_month' => $month?->value(),
            'data_is_approximate' => true,
        ];
    }

    private function sourceMonth(CrimeResults $result): ?string
    {
        foreach ($result->crimes as $record) {
            if ($record->month !== null) {
                return $record->month;
            }
        }

        return null;
    }
}

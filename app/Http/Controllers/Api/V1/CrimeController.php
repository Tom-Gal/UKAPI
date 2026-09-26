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
use Knuckles\Scribe\Attributes\Response;

/**
 * @group Crime data
 *
 * Approximate, anonymised street-level crime data from Police.uk. Coverage is England, Wales and Northern Ireland; Scotland includes British Transport Police data only.
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
], status: 503, description: 'Police.uk is temporarily unavailable')]
final class CrimeController extends Controller
{
    /**
     * Find crime near a postcode.
     *
     * Returns approximate street-level crime records near a postcode.
     */
    #[Response(content: [
        'data' => [
            'postcode' => 'BL2 6XX',
            'crimes' => [[
                'category' => 'anti-social-behaviour', 'persistent_id' => null, 'month' => '2026-07',
                'location' => ['latitude' => 53.592132, 'longitude' => -2.411624, 'street' => ['id' => 123456, 'name' => 'On or near Example Street'], 'approximate' => true],
                'outcome' => ['category' => null, 'month' => null],
            ]],
        ],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'police_uk',
            'coverage' => 'England, Wales and Northern Ireland; Scotland has British Transport Police data only.',
            'requested_month' => '2026-07', 'data_is_approximate' => true,
        ],
    ], description: 'Approximate crime records')]
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

    /**
     * Summarise crime near a postcode.
     *
     * Aggregates approximate street-level crime records by category.
     */
    #[Response(content: [
        'data' => [
            'postcode' => 'BL2 6XX', 'month' => '2026-07', 'total' => 2,
            'by_category' => [['category' => 'anti-social-behaviour', 'count' => 1], ['category' => 'violent-crime', 'count' => 1]],
        ],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'police_uk',
            'coverage' => 'England, Wales and Northern Ireland; Scotland has British Transport Police data only.',
            'requested_month' => '2026-07', 'data_is_approximate' => true,
        ],
    ], description: 'Crime category summary')]
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

    /**
     * List crime categories.
     *
     * Lists Police.uk crime categories for an optional month.
     */
    #[Response(content: [
        'data' => ['categories' => [['code' => 'all-crime', 'name' => 'All crime and anti-social behaviour']]],
        'meta' => [
            'request_id' => 'req_01j8d5s1wdm85crh9b739hw4gd', 'cached' => false, 'source' => 'police_uk',
            'coverage' => 'England, Wales and Northern Ireland; Scotland has British Transport Police data only.',
            'requested_month' => '2026-07', 'data_is_approximate' => true,
        ],
    ], description: 'Crime categories')]
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

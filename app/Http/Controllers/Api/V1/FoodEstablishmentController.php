<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Food\FoodEstablishmentCollection;
use App\Domain\Food\FoodEstablishmentData;
use App\Domain\Food\FoodEstablishmentId;
use App\Domain\Food\FoodEstablishmentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FoodEstablishmentNearbyRequest;
use App\Http\Requests\Api\V1\FoodEstablishmentSearchRequest;
use App\Support\Api\ApiResponse;
use App\Support\Api\Pagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FoodEstablishmentController extends Controller
{
    public function show(Request $request, string $id, FoodEstablishmentService $service): JsonResponse
    {
        $result = $service->find(FoodEstablishmentId::from($id));

        return ApiResponse::success($request, $result->establishment->toArray(), [
            'cached' => $result->cached,
            'stale' => $result->stale,
            'source' => 'fsa_food_hygiene',
            'source_updated_at' => null,
            'coverage' => 'UK, participating local authorities',
        ]);
    }

    public function search(FoodEstablishmentSearchRequest $request, FoodEstablishmentService $service): JsonResponse
    {
        $search = $request->search();
        $result = $service->search($search);

        return ApiResponse::success($request, $this->items($result), $this->meta($result, $search->page, $search->perPage));
    }

    public function nearby(FoodEstablishmentNearbyRequest $request, FoodEstablishmentService $service): JsonResponse
    {
        $result = $service->nearby(
            postcode: $request->postcode(),
            radiusKilometres: $request->radiusKilometres(),
            schemeType: $request->scheme(),
            rating: $request->rating(),
            ratingOperator: $request->ratingOperator(),
            page: $request->pageNumber(),
            perPage: $request->perPage(),
        );

        return ApiResponse::success($request, $this->items($result), $this->meta($result, $request->pageNumber(), $request->perPage()));
    }

    /** @return list<array<string, mixed>> */
    private function items(FoodEstablishmentCollection $result): array
    {
        return array_map(static fn (FoodEstablishmentData $item): array => $item->toArray(), $result->establishments);
    }

    /** @return array<string, mixed> */
    private function meta(FoodEstablishmentCollection $result, int $page, int $perPage): array
    {
        return [
            'cached' => $result->cached,
            'stale' => $result->stale,
            'source' => 'fsa_food_hygiene',
            'source_updated_at' => $result->sourceUpdatedAt,
            'coverage' => 'UK, participating local authorities',
            'pagination' => Pagination::from($page, $perPage, $result->total)->toArray(),
        ];
    }
}

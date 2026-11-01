<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Planning\PlanningConstraintCollection;
use App\Domain\Planning\PlanningConstraintData;
use App\Domain\Planning\PlanningConstraintService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlanningConstraintRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PlanningController extends Controller
{
    public function constraints(PlanningConstraintRequest $request, PlanningConstraintService $service): JsonResponse
    {
        $result = $service->constraints($request->constraints());

        return ApiResponse::success($request, $this->items($result), [
            'cached' => $result->cached,
            'stale' => $result->stale,
            'source' => 'planning_data',
            'source_updated_at' => null,
            'coverage' => 'England',
            'datasets' => PlanningConstraintData::DATASETS,
            'dataset_sources' => $this->datasetSources(),
            'disclaimer' => 'Planning Data is incomplete and in development. An empty result does not prove that no planning constraint exists, and this endpoint is not an official property search.',
        ]);
    }

    /** @return list<array<string, string>> */
    private function datasetSources(): array
    {
        $datasets = config('ukapi.planning_data.datasets', []);

        if (! is_array($datasets)) {
            return [];
        }

        $sources = [];
        foreach (PlanningConstraintData::DATASETS as $dataset) {
            $metadata = $datasets[$dataset] ?? null;

            if (! is_array($metadata)) {
                continue;
            }

            $sources[] = [
                'dataset' => $dataset,
                'label' => (string) ($metadata['label'] ?? $dataset),
                'source' => (string) ($metadata['source'] ?? 'Planning Data'),
                'licence' => (string) ($metadata['licence'] ?? 'Open Government Licence v3.0'),
                'attribution' => (string) ($metadata['attribution'] ?? ''),
                'limitation' => (string) ($metadata['limitation'] ?? ''),
                'source_url' => (string) ($metadata['source_url'] ?? 'https://www.planning.data.gov.uk/docs'),
            ];
        }

        return $sources;
    }

    /** @return list<array<string, mixed>> */
    private function items(PlanningConstraintCollection $result): array
    {
        return array_map(
            static fn (PlanningConstraintData $constraint): array => $constraint->toArray(),
            $result->constraints,
        );
    }
}

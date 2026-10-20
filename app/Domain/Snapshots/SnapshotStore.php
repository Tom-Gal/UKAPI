<?php

namespace App\Domain\Snapshots;

use App\Models\DatasetSnapshot;
use App\Models\DatasetSnapshotHead;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SnapshotStore
{
    public function active(string $dataset, string $scopeKey): ?DatasetSnapshot
    {
        return DatasetSnapshotHead::query()
            ->with('snapshot')
            ->where('dataset', $dataset)
            ->where('scope_key', $scopeKey)
            ->first()
            ?->snapshot;
    }

    public function find(string $dataset, string $version): ?DatasetSnapshot
    {
        return DatasetSnapshot::query()
            ->where('dataset', $dataset)
            ->where('version', $version)
            ->first();
    }

    /**
     * Insert an immutable snapshot and atomically switch the active pointer.
     *
     * @param  list<array<string, mixed>>  $payload
     */
    public function capture(
        string $dataset,
        string $scopeKey,
        string $source,
        array $payload,
        ?CarbonImmutable $sourceUpdatedAt = null,
    ): DatasetSnapshot {
        $canonical = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );

        return DB::transaction(function () use ($dataset, $scopeKey, $source, $payload, $sourceUpdatedAt, $canonical): DatasetSnapshot {
            $snapshot = DatasetSnapshot::query()->create([
                'dataset' => $dataset,
                'scope_key' => $scopeKey,
                'version' => (string) Str::ulid(),
                'source' => $source,
                'captured_at' => now(),
                'source_updated_at' => $sourceUpdatedAt,
                'checksum' => hash('sha256', $canonical),
                'record_count' => count($payload),
                'payload' => $payload,
            ]);

            $this->activate($snapshot);

            return $snapshot;
        });
    }

    /**
     * Capture a new version only when the canonical payload has changed.
     *
     * @param  list<array<string, mixed>>  $payload
     */
    public function captureIfChanged(
        string $dataset,
        string $scopeKey,
        string $source,
        array $payload,
        ?CarbonImmutable $sourceUpdatedAt = null,
    ): DatasetSnapshot {
        $active = $this->active($dataset, $scopeKey);
        $checksum = $this->checksum($payload);

        if ($active?->checksum === $checksum) {
            return $active;
        }

        return $this->capture($dataset, $scopeKey, $source, $payload, $sourceUpdatedAt);
    }

    public function activate(DatasetSnapshot $snapshot): DatasetSnapshotHead
    {
        return DatasetSnapshotHead::query()->updateOrCreate(
            ['dataset' => $snapshot->dataset, 'scope_key' => $snapshot->scope_key],
            ['snapshot_id' => $snapshot->id, 'switched_at' => now()],
        );
    }

    /** @return list<array<string, mixed>> */
    public function payload(DatasetSnapshot $snapshot): array
    {
        return array_values(array_filter(
            $snapshot->payload,
            static fn (mixed $record): bool => is_array($record),
        ));
    }

    /**
     * @param  Closure(array<string, mixed>): string  $identity
     * @return array{added: list<array<string, mixed>>, removed: list<array<string, mixed>>, changed: list<array{key: string, before: array<string, mixed>, after: array<string, mixed>}>}
     */
    public function diff(DatasetSnapshot $from, DatasetSnapshot $to, Closure $identity): array
    {
        $before = $this->index($this->payload($from), $identity);
        $after = $this->index($this->payload($to), $identity);
        $added = array_diff_key($after, $before);
        $removed = array_diff_key($before, $after);
        $changed = [];

        foreach (array_intersect_key($before, $after) as $key => $record) {
            if ($this->canonical($record) !== $this->canonical($after[$key])) {
                $changed[] = [
                    'key' => (string) $key,
                    'before' => $record,
                    'after' => $after[$key],
                ];
            }
        }

        ksort($added, SORT_STRING);
        ksort($removed, SORT_STRING);
        usort($changed, static fn (array $left, array $right): int => strcmp($left['key'], $right['key']));

        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
            'changed' => $changed,
        ];
    }

    public function prune(CarbonImmutable $before, ?string $dataset = null): int
    {
        $activeIds = DatasetSnapshotHead::query()->select('snapshot_id');
        $query = DatasetSnapshot::query()
            ->where('created_at', '<', $before)
            ->whereNotIn('id', $activeIds);

        if ($dataset !== null) {
            $query->where('dataset', $dataset);
        }

        return $query->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  Closure(array<string, mixed>): string  $identity
     * @return array<string, array<string, mixed>>
     */
    private function index(array $records, Closure $identity): array
    {
        $indexed = [];
        foreach ($records as $record) {
            $indexed[$identity($record)] = $record;
        }

        ksort($indexed, SORT_STRING);

        return $indexed;
    }

    /** @param array<string, mixed> $value */
    private function canonical(array $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );
    }

    /** @param list<array<string, mixed>> $payload */
    private function checksum(array $payload): string
    {
        return hash('sha256', json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        ));
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Snapshots\SnapshotStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SnapshotStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_switches_the_active_version_without_mutating_old_snapshots(): void
    {
        $store = app(SnapshotStore::class);
        $first = $store->capture('test.dataset', 'scope-a', 'test', [
            ['id' => 'a', 'value' => 1],
        ]);
        $second = $store->capture('test.dataset', 'scope-a', 'test', [
            ['id' => 'a', 'value' => 2],
        ]);

        $this->assertNotSame($first->version, $second->version);
        $this->assertSame($second->id, $store->active('test.dataset', 'scope-a')?->id);
        $this->assertSame([['id' => 'a', 'value' => 1]], $first->fresh()->payload);
        $this->assertSame([['id' => 'a', 'value' => 2]], $second->fresh()->payload);
        $store->activate($first);

        $this->assertSame($first->id, $store->active('test.dataset', 'scope-a')?->id);
        $this->assertDatabaseHas('dataset_snapshot_heads', [
            'dataset' => 'test.dataset',
            'scope_key' => 'scope-a',
            'snapshot_id' => $first->id,
        ]);
    }

    public function test_diff_is_deterministic_and_classifies_added_removed_and_changed_records(): void
    {
        $store = app(SnapshotStore::class);
        $from = $store->capture('test.dataset', 'from', 'test', [
            ['id' => 'b', 'value' => 'removed'],
            ['id' => 'a', 'value' => 'before'],
        ]);
        $to = $store->capture('test.dataset', 'to', 'test', [
            ['id' => 'c', 'value' => 'added'],
            ['id' => 'a', 'value' => 'after'],
        ]);
        $identity = static fn (array $record): string => (string) $record['id'];

        $first = $store->diff($from, $to, $identity);
        $second = $store->diff($from, $to, $identity);

        $this->assertSame($first, $second);
        $this->assertSame([['id' => 'c', 'value' => 'added']], $first['added']);
        $this->assertSame([['id' => 'b', 'value' => 'removed']], $first['removed']);
        $this->assertSame([[
            'key' => 'a',
            'before' => ['id' => 'a', 'value' => 'before'],
            'after' => ['id' => 'a', 'value' => 'after'],
        ]], $first['changed']);
    }

    public function test_prune_removes_old_inactive_snapshots_but_preserves_the_active_version(): void
    {
        $store = app(SnapshotStore::class);

        $this->travelTo('2025-01-01 00:00:00');
        $old = $store->capture('test.dataset', 'scope-a', 'test', [['id' => 'old']]);
        $this->travelTo('2026-02-01 00:00:00');
        $active = $store->capture('test.dataset', 'scope-a', 'test', [['id' => 'active']]);

        $deleted = $store->prune(now()->subDays(365), 'test.dataset');

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('dataset_snapshots', ['id' => $old->id]);
        $this->assertDatabaseHas('dataset_snapshots', ['id' => $active->id]);
        $this->travelBack();
    }

    public function test_recursive_object_key_order_does_not_change_checksums_versions_or_diffs(): void
    {
        $store = app(SnapshotStore::class);
        $original = [[
            'id' => 'a',
            'metadata' => ['z' => null, 'a' => ['z' => false, 'a' => 'é/river']],
            'samples' => [['z' => 1.5, 'a' => 2], ['b' => 3, 'a' => 4]],
        ]];
        $reordered = [[
            'samples' => [['a' => 2, 'z' => 1.5], ['a' => 4, 'b' => 3]],
            'metadata' => ['a' => ['a' => 'é/river', 'z' => false], 'z' => null],
            'id' => 'a',
        ]];
        $from = $store->capture('test.dataset', 'scope-a', 'test', $original);
        $unchanged = $store->captureIfChanged('test.dataset', 'scope-a', 'test', $reordered);

        $this->assertSame($from->id, $unchanged->id);
        $this->assertDatabaseCount('dataset_snapshots', 1);

        $to = $store->capture('test.dataset', 'scope-a', 'test', $reordered);
        $this->assertSame($from->checksum, $to->checksum);
        $this->assertSame(['added' => [], 'removed' => [], 'changed' => []], $store->diff(
            $from,
            $to,
            static fn (array $record): string => $record['id'],
        ));
        $this->assertSame($original, $from->payload);
        $this->assertSame($reordered, $to->payload);
    }

    #[DataProvider('meaningfulValueChanges')]
    public function test_canonicalization_preserves_list_order_and_scalar_types(mixed $before, mixed $after): void
    {
        $store = app(SnapshotStore::class);
        $from = $store->capture('test.dataset', 'scope-a', 'test', [['id' => 'a', 'value' => $before]]);
        $to = $store->captureIfChanged('test.dataset', 'scope-a', 'test', [['id' => 'a', 'value' => $after]]);

        $this->assertNotSame($from->id, $to->id);
        $this->assertNotSame($from->checksum, $to->checksum);
        $diff = $store->diff($from, $to, static fn (array $record): string => $record['id']);
        $this->assertCount(1, $diff['changed']);
        $this->assertSame($before, $diff['changed'][0]['before']['value']);
        $this->assertSame($after, $diff['changed'][0]['after']['value']);
    }

    /** @return array<string, array{mixed, mixed}> */
    public static function meaningfulValueChanges(): array
    {
        return [
            'nested list order' => [['samples' => [1, 2]], ['samples' => [2, 1]]],
            'list of objects order' => [[['id' => 'a'], ['id' => 'b']], [['id' => 'b'], ['id' => 'a']]],
            'list multiplicity' => [[1, 1], [1]],
            'number versus string' => [1, '1'],
            'boolean versus integer' => [false, 0],
            'null versus empty list' => [null, []],
            'numeric object versus list' => [[1 => 'b', 0 => 'a'], ['a', 'b']],
        ];
    }

    public function test_canonicalization_preserves_top_level_record_order(): void
    {
        $store = app(SnapshotStore::class);
        $records = [['id' => 'a'], ['id' => 'b']];
        $from = $store->capture('test.dataset', 'scope-a', 'test', $records);
        $to = $store->captureIfChanged('test.dataset', 'scope-a', 'test', array_reverse($records));

        $this->assertNotSame($from->checksum, $to->checksum);
        $this->assertNotSame($from->id, $to->id);
        $this->assertSame(['added' => [], 'removed' => [], 'changed' => []], $store->diff(
            $from,
            $to,
            static fn (array $record): string => $record['id'],
        ));
    }

    public function test_checksums_preserve_zero_fractions_and_unescaped_unicode_and_slashes(): void
    {
        $store = app(SnapshotStore::class);
        $integer = $store->capture('test.dataset', 'scope-a', 'test', [['value' => 1, 'id' => 'é/river']]);
        $float = $store->captureIfChanged('test.dataset', 'scope-a', 'test', [['value' => 1.0, 'id' => 'é/river']]);

        $this->assertNotSame($integer->id, $float->id);
        $this->assertSame(hash('sha256', '[{"id":"é/river","value":1}]'), $integer->checksum);
        $this->assertSame(hash('sha256', '[{"id":"é/river","value":1.0}]'), $float->checksum);
    }

    public function test_diff_keeps_distinct_numeric_string_identities_separate(): void
    {
        $store = app(SnapshotStore::class);
        $from = $store->capture('test.dataset', 'from', 'test', []);
        $records = [['id' => '0'], ['id' => '00'], ['id' => '01'], ['id' => '1']];
        $to = $store->capture('test.dataset', 'to', 'test', array_reverse($records));

        $this->assertSame(['added' => $records, 'removed' => [], 'changed' => []], $store->diff(
            $from,
            $to,
            static fn (array $record): string => $record['id'],
        ));
    }

    public function test_canonicalization_sorts_numeric_object_keys_and_preserves_empty_objects(): void
    {
        $store = app(SnapshotStore::class);
        $from = $store->capture('test.dataset', 'scope-a', 'test', [[
            'id' => 'a',
            'value' => (object) ['1' => 'b', '0' => 'a'],
            'empty' => (object) [],
        ]]);
        $unchanged = $store->captureIfChanged('test.dataset', 'scope-a', 'test', [[
            'empty' => (object) [],
            'value' => (object) ['0' => 'a', '1' => 'b'],
            'id' => 'a',
        ]]);

        $this->assertSame($from->id, $unchanged->id);
        $this->assertSame(hash('sha256', '[{"empty":{},"id":"a","value":{"0":"a","1":"b"}}]'), $from->checksum);
    }

    #[DataProvider('duplicateIdentities')]
    public function test_diff_rejects_duplicate_identities_on_either_side(string $side, string $key, bool $identical): void
    {
        $store = app(SnapshotStore::class);
        $records = [
            ['id' => $key, 'value' => 1],
            ['id' => $key, 'value' => $identical ? 1 : 2],
        ];
        $from = $store->capture('test.dataset', 'from', 'test', $side === 'from' ? $records : []);
        $to = $store->capture('test.dataset', 'to', 'test', $side === 'to' ? $records : []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Duplicate snapshot diff identity: "%s".', $key));

        $store->diff($from, $to, static fn (array $record): string => $record['id']);
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function duplicateIdentities(): iterable
    {
        foreach (['from', 'to'] as $side) {
            foreach (['a', '0', ''] as $key) {
                foreach ([true, false] as $identical) {
                    yield $side.'-'.$key.'-'.($identical ? 'identical' : 'different') => [$side, $key, $identical];
                }
            }
        }
    }
}

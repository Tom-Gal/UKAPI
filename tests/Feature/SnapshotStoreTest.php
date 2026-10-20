<?php

namespace Tests\Feature;

use App\Domain\Snapshots\SnapshotStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

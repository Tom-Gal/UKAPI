<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RecoveryManifest
{
    /** @return array<string, mixed> */
    public function generate(): array
    {
        $requiredTables = [
            'migrations',
            'users',
            'subscriptions',
            'subscription_items',
            'plans',
            'plan_prices',
            'api_keys',
            'monthly_usage',
            'monthly_endpoint_usage',
            'daily_usage',
            'daily_usage_rejections',
            'dataset_snapshots',
            'dataset_snapshot_heads',
            'service_health_checks',
            'jobs',
            'failed_jobs',
        ];
        $missingTables = array_values(array_filter(
            $requiredTables,
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));

        $checks = [
            'tables' => array_combine(
                $requiredTables,
                array_map(fn (string $table): array => $this->tableSummary($table), $requiredTables),
            ),
            'migrations' => $this->migrationSummary(),
            'users' => $this->userSummary(),
            'subscriptions' => $this->subscriptionSummary(),
            'plans' => $this->planSummary(),
            'usage' => $this->usageSummary(),
            'snapshots' => $this->snapshotSummary(),
            'health' => $this->healthSummary(),
            'queue' => $this->queueSummary(),
            'integrity' => $this->integritySummary(),
        ];

        return [
            'schema_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'application_version' => (string) config('app.version'),
            'database_connection' => (string) config('database.default'),
            'valid' => $missingTables === []
                && ! in_array(false, $checks['integrity'], true)
                && ($checks['snapshots']['sic_active'] ?? null) !== null,
            'missing_tables' => $missingTables,
            'checks' => $checks,
        ];
    }

    /** @return array{present: bool, count: int|null} */
    private function tableSummary(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return ['present' => false, 'count' => null];
        }

        return ['present' => true, 'count' => DB::table($table)->count()];
    }

    /** @return array<string, mixed> */
    private function migrationSummary(): array
    {
        if (! Schema::hasTable('migrations')) {
            return ['count' => null, 'max_batch' => null, 'checksum' => null];
        }

        $rows = DB::table('migrations')->select(['migration', 'batch'])->orderBy('migration')->get();

        return [
            'count' => $rows->count(),
            'max_batch' => (int) ($rows->max('batch') ?? 0),
            'checksum' => $this->checksum($rows->all()),
        ];
    }

    /** @return array<string, int|null> */
    private function userSummary(): array
    {
        if (! Schema::hasTable('users')) {
            return ['count' => null, 'verified' => null, 'administrators' => null];
        }

        return [
            'count' => DB::table('users')->count(),
            'verified' => DB::table('users')->whereNotNull('email_verified_at')->count(),
            'administrators' => DB::table('users')->where('is_admin', true)->count(),
        ];
    }

    /** @return array<string, int|null> */
    private function subscriptionSummary(): array
    {
        if (! Schema::hasTable('subscriptions')) {
            return ['count' => null, 'active' => null, 'ended' => null, 'items' => null];
        }

        return [
            'count' => DB::table('subscriptions')->count(),
            'active' => DB::table('subscriptions')->whereNull('ends_at')->count(),
            'ended' => DB::table('subscriptions')->whereNotNull('ends_at')->count(),
            'items' => Schema::hasTable('subscription_items') ? DB::table('subscription_items')->count() : null,
        ];
    }

    /** @return array<string, mixed> */
    private function planSummary(): array
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('plan_prices')) {
            return ['plans' => null, 'prices' => null, 'checksum' => null];
        }

        $plans = DB::table('plans')->select([
            'id', 'key', 'label', 'features', 'monthly_request_quota', 'burst_requests_per_second',
            'max_page_size', 'commercial_use', 'active',
        ])->orderBy('key')->get();
        $prices = DB::table('plan_prices')->select([
            'plan_id', 'stripe_price', 'interval', 'amount', 'currency', 'active',
        ])->orderBy('plan_id')->orderBy('interval')->orderBy('stripe_price')->get();

        return [
            'plans' => $plans->count(),
            'prices' => $prices->count(),
            'checksum' => $this->checksum([$plans->all(), $prices->all()]),
        ];
    }

    /** @return array<string, int|null> */
    private function usageSummary(): array
    {
        return [
            'monthly_accounts' => $this->count('monthly_usage'),
            'monthly_requests' => $this->sum('monthly_usage', 'requests'),
            'monthly_endpoint_rows' => $this->count('monthly_endpoint_usage'),
            'monthly_endpoint_requests' => $this->sum('monthly_endpoint_usage', 'requests'),
            'daily_accounts' => $this->count('daily_usage'),
            'daily_requests' => $this->sum('daily_usage', 'requests'),
            'rejection_rows' => $this->count('daily_usage_rejections'),
            'rejections' => $this->sum('daily_usage_rejections', 'requests'),
        ];
    }

    /** @return array<string, mixed> */
    private function snapshotSummary(): array
    {
        if (! Schema::hasTable('dataset_snapshots') || ! Schema::hasTable('dataset_snapshot_heads')) {
            return ['snapshots' => null, 'heads' => null, 'records' => null, 'heads_checksum' => null, 'sic_active' => null];
        }

        $heads = DB::table('dataset_snapshot_heads as heads')
            ->join('dataset_snapshots as snapshots', 'snapshots.id', '=', 'heads.snapshot_id')
            ->select([
                'heads.dataset', 'heads.scope_key', 'snapshots.version', 'snapshots.checksum', 'snapshots.record_count',
            ])
            ->orderBy('heads.dataset')->orderBy('heads.scope_key')->get();
        $sic = $heads->first(fn (object $head): bool => $head->dataset === 'companies_house.sic_2007' && $head->scope_key === 'reference');

        return [
            'snapshots' => DB::table('dataset_snapshots')->count(),
            'heads' => $heads->count(),
            'records' => (int) DB::table('dataset_snapshots')->sum('record_count'),
            'heads_checksum' => $this->checksum($heads->all()),
            'sic_active' => $sic === null ? null : [
                'version' => $sic->version,
                'checksum' => $sic->checksum,
                'record_count' => (int) $sic->record_count,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function healthSummary(): array
    {
        if (! Schema::hasTable('service_health_checks')) {
            return ['services' => null, 'statuses' => []];
        }

        return [
            'services' => DB::table('service_health_checks')->count(),
            'statuses' => DB::table('service_health_checks')
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')->orderBy('status')
                ->pluck('aggregate', 'status')->map(static fn (mixed $count): int => (int) $count)->all(),
        ];
    }

    /** @return array<string, int|null> */
    private function queueSummary(): array
    {
        return [
            'pending' => $this->count('jobs'),
            'failed' => $this->count('failed_jobs'),
        ];
    }

    /** @return array<string, bool> */
    private function integritySummary(): array
    {
        return [
            'subscriptions_have_users' => $this->hasNoOrphans('subscriptions', 'user_id', 'users'),
            'subscription_items_have_subscriptions' => $this->hasNoOrphans('subscription_items', 'subscription_id', 'subscriptions'),
            'monthly_usage_has_users' => $this->hasNoOrphans('monthly_usage', 'user_id', 'users'),
            'snapshot_heads_have_snapshots' => $this->hasNoOrphans('dataset_snapshot_heads', 'snapshot_id', 'dataset_snapshots'),
        ];
    }

    private function count(string $table): ?int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : null;
    }

    private function sum(string $table, string $column): ?int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->sum($column) : null;
    }

    private function hasNoOrphans(string $child, string $foreignKey, string $parent): bool
    {
        if (! Schema::hasTable($child) || ! Schema::hasTable($parent)) {
            return false;
        }

        return ! DB::table("{$child} as child")
            ->leftJoin("{$parent} as parent", 'parent.id', '=', "child.{$foreignKey}")
            ->whereNotNull("child.{$foreignKey}")
            ->whereNull('parent.id')
            ->exists();
    }

    private function checksum(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}

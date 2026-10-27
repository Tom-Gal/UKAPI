<?php

namespace App\Domain\Operations;

use App\Domain\Billing\PlanCatalog;
use App\Domain\Health\HealthStatus;
use App\Models\AdminAuditEvent;
use App\Models\ApiKey;
use App\Models\DatasetSnapshotHead;
use App\Models\NotificationDeliveryEvent;
use App\Models\ServiceHealthCheck;
use App\Models\ServiceHealthIncident;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription as CashierSubscription;
use stdClass;

final class AdminOperations
{
    public function __construct(private readonly PlanCatalog $plans) {}

    /** @return array<string, int> */
    public function headline(): array
    {
        $today = CarbonImmutable::now('UTC')->toDateString();

        return [
            'open_incidents' => ServiceHealthIncident::query()->whereNull('recovered_at')->count(),
            'unacknowledged_incidents' => ServiceHealthIncident::query()
                ->whereNull('recovered_at')->whereNull('acknowledged_at')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'notification_failures_24h' => NotificationDeliveryEvent::query()
                ->where('status', 'failed')->where('occurred_at', '>=', now()->subDay())->count(),
            'rejections_today' => (int) DB::table('daily_usage_rejections')
                ->where('date', $today)->sum('requests'),
        ];
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        return [
            'endpointMetrics' => $this->endpointMetrics(),
            'providerMetrics' => $this->providerMetrics(),
            'incidents' => $this->incidents(),
            'hotspots' => $this->hotspots(),
            'queueHealth' => $this->queueHealth(),
            'notificationHealth' => $this->notificationHealth(),
            'snapshots' => $this->snapshots(),
            'auditEvents' => $this->auditEvents(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function users(): array
    {
        $users = User::query()
            ->with(['subscriptions.items', 'apiKeys' => fn ($query) => $query->latest()])
            ->latest()
            ->latest('id')
            ->limit(50)
            ->get();
        $userIds = $users->pluck('id');
        $month = CarbonImmutable::now('UTC')->startOfMonth();
        $usage = DB::table('monthly_usage')->whereIn('user_id', $userIds)
            ->where('month', $month->toDateString())->pluck('requests', 'user_id');
        $rejections = DB::table('daily_usage_rejections')->whereIn('user_id', $userIds)
            ->whereBetween('date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->selectRaw('user_id, reason, SUM(requests) AS requests')->groupBy('user_id', 'reason')->get()
            ->groupBy('user_id');

        return array_values($users->map(function (User $user) use ($usage, $rejections): array {
            $plan = $this->plans->forUser($user);
            $subscription = $user->subscriptions->where('type', 'default')->sortByDesc('created_at')->first();
            $subscription = $subscription instanceof CashierSubscription ? $subscription : null;
            $userRejections = $rejections->get($user->id, collect());

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String() ?? '',
                'plan' => $plan['label'],
                'monthly_usage' => (int) $usage->get($user->id, 0),
                'monthly_quota' => $plan['monthly_request_quota'],
                'rate_limit_rejections' => (int) $userRejections->where('reason', 'rate_limit')->sum('requests'),
                'quota_rejections' => (int) $userRejections->where('reason', 'quota')->sum('requests'),
                'subscription' => $subscription ? [
                    'status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                    'stripe_id' => $subscription->stripe_id,
                ] : null,
                'stripe_url' => $this->stripeCustomerUrl($user->stripe_id),
                'api_keys' => $user->apiKeys->take(5)->map(fn (ApiKey $key): array => [
                    'id' => $key->id,
                    'name' => $key->name,
                    'prefix' => "uk_{$key->environment}_{$key->public_id}",
                    'environment' => $key->environment,
                    'status' => $key->status,
                    'expires_at' => $key->expires_at?->toIso8601String(),
                ])->values()->all(),
            ];
        })->all());
    }

    /** @return list<array<string, mixed>> */
    private function endpointMetrics(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('ukapi.usage_endpoint_labels', []);
        $rows = DB::table('endpoint_daily_metrics')
            ->where('date', '>=', CarbonImmutable::now('UTC')->subDays(6)->toDateString())
            ->get()->groupBy('endpoint');
        $metrics = [];

        foreach ($rows as $endpoint => $endpointRows) {
            $requests = (int) $endpointRows->sum('requests');
            $errors = (int) $endpointRows
                ->whereIn('status_family', ['4xx', '5xx'])->sum('requests');
            $totalLatency = (int) $endpointRows->sum('total_latency_ms');
            $metrics[] = [
                'endpoint' => (string) $endpoint,
                'label' => $labels[$endpoint] ?? $endpoint,
                'requests' => $requests,
                'errors' => $errors,
                'error_rate' => $requests > 0 ? round(($errors / $requests) * 100, 1) : 0.0,
                'average_latency_ms' => $requests > 0 ? (int) round($totalLatency / $requests) : 0,
                'p95_latency_ms' => $this->percentileBucket($endpointRows, $requests, 0.95),
                'max_latency_ms' => (int) $endpointRows->max('max_latency_ms'),
            ];
        }

        usort($metrics, static fn (array $left, array $right): int => $right['requests'] <=> $left['requests']);

        return array_slice($metrics, 0, 25);
    }

    /** @return list<array<string, mixed>> */
    private function providerMetrics(): array
    {
        return array_values(ServiceHealthCheck::query()->orderBy('service')->get()->map(fn (ServiceHealthCheck $check): array => [
            'service' => $check->service,
            'name' => HealthStatus::SERVICES[$check->service] ?? $check->service,
            'status' => $check->status,
            'failure_reason' => $check->failure_reason,
            'latency_ms' => $check->latency_ms,
            'consecutive_failures' => $check->consecutive_failures,
            'last_checked_at' => $check->last_checked_at?->toIso8601String(),
        ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function incidents(): array
    {
        return array_values(ServiceHealthIncident::query()->with('acknowledgedBy:id,name')
            ->orderByRaw('recovered_at IS NULL DESC')->latest('last_observed_at')->limit(50)->get()
            ->map(fn (ServiceHealthIncident $incident): array => [
                'id' => $incident->id,
                'service' => $incident->service,
                'failure_reason' => $incident->failure_reason,
                'started_at' => $incident->started_at->toIso8601String(),
                'last_observed_at' => $incident->last_observed_at->toIso8601String(),
                'recovered_at' => $incident->recovered_at?->toIso8601String(),
                'occurrences' => $incident->occurrences,
                'latest_latency_ms' => $incident->latest_latency_ms,
                'acknowledged_at' => $incident->acknowledged_at?->toIso8601String(),
                'acknowledged_by' => $incident->acknowledgedBy?->name,
                'acknowledgement_note' => $incident->acknowledgement_note,
            ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function hotspots(): array
    {
        $month = CarbonImmutable::now('UTC')->startOfMonth();
        $rows = DB::table('daily_usage_rejections')
            ->whereBetween('date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->selectRaw('user_id, reason, SUM(requests) AS requests')->groupBy('user_id', 'reason')->get()
            ->groupBy('user_id');
        $users = User::query()->whereIn('id', $rows->keys())->get()->keyBy('id');
        $hotspots = [];
        foreach ($rows as $userId => $userRows) {
            $user = $users->get($userId);
            if (! $user instanceof User) {
                continue;
            }

            $rate = (int) $userRows->where('reason', 'rate_limit')->sum('requests');
            $quota = (int) $userRows->where('reason', 'quota')->sum('requests');
            $hotspots[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rate_limit_rejections' => $rate,
                'quota_rejections' => $quota,
                'total_rejections' => $rate + $quota,
            ];
        }
        usort($hotspots, static fn (array $left, array $right): int => $right['total_rejections'] <=> $left['total_rejections']);

        return array_slice($hotspots, 0, 20);
    }

    /** @return array<string, mixed> */
    private function queueHealth(): array
    {
        $oldest = DB::table('jobs')->min('created_at');

        return [
            'connection' => (string) config('queue.default'),
            'pending' => DB::table('jobs')->count(),
            'oldest_age_seconds' => is_numeric($oldest) ? max(0, now()->getTimestamp() - (int) $oldest) : null,
            'failed' => DB::table('failed_jobs')->count(),
            'recent_failures' => DB::table('failed_jobs')->select(['uuid', 'connection', 'queue', 'failed_at'])
                ->latest('failed_at')->limit(20)->get()->map(fn (object $row): array => [
                    'uuid' => (string) $row->uuid,
                    'connection' => (string) $row->connection,
                    'queue' => (string) $row->queue,
                    'failed_at' => CarbonImmutable::parse($row->failed_at)->toIso8601String(),
                ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function notificationHealth(): array
    {
        $since = now()->subDay();
        $events = NotificationDeliveryEvent::query()->where('occurred_at', '>=', $since)->get();

        return [
            'sent_24h' => $events->where('status', 'sent')->count(),
            'failed_24h' => $events->where('status', 'failed')->count(),
            'recent_failures' => NotificationDeliveryEvent::query()->with('user:id,name')
                ->where('status', 'failed')->latest('occurred_at')->limit(20)->get()
                ->map(fn (NotificationDeliveryEvent $event): array => [
                    'notification' => $event->notification,
                    'channel' => $event->channel,
                    'failure_type' => $event->failure_type,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                    'user' => $event->user?->name,
                ])->values()->all(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function snapshots(): array
    {
        return array_values(DatasetSnapshotHead::query()->with('snapshot')->latest('switched_at')->limit(100)->get()
            ->map(fn (DatasetSnapshotHead $head): array => [
                'dataset' => $head->dataset,
                'scope' => $head->scope_key,
                'version' => $head->snapshot->version,
                'source' => $head->snapshot->source,
                'captured_at' => $head->snapshot->captured_at?->toIso8601String(),
                'source_updated_at' => $head->snapshot->source_updated_at?->toIso8601String(),
                'record_count' => $head->snapshot->record_count,
                'age_hours' => $head->snapshot->captured_at?->diffInHours(now()),
            ])->all());
    }

    /** @return list<array<string, mixed>> */
    private function auditEvents(): array
    {
        return array_values(AdminAuditEvent::query()->with('actor:id,name')->latest()->limit(50)->get()
            ->map(fn (AdminAuditEvent $event): array => [
                'id' => $event->id,
                'actor' => $event->actor_user_id === null ? 'Former administrator' : $event->actor->name,
                'action' => $event->action,
                'subject_type' => $event->subject_type,
                'subject_id' => $event->subject_id,
                'reason' => $event->reason,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->all());
    }

    /** @param Collection<int, stdClass> $rows */
    private function percentileBucket(Collection $rows, int $total, float $percentile): int
    {
        if ($total < 1) {
            return 0;
        }

        $target = (int) ceil($total * $percentile);
        $seen = 0;
        foreach ($rows->groupBy('latency_bucket_ms')->sortKeys() as $bucket => $bucketRows) {
            $seen += (int) $bucketRows->sum('requests');
            if ($seen >= $target) {
                return (int) $bucket;
            }
        }

        return (int) $rows->max('latency_bucket_ms');
    }

    private function stripeCustomerUrl(?string $customerId): ?string
    {
        if (! is_string($customerId) || preg_match('/^cus_[A-Za-z0-9]+$/', $customerId) !== 1) {
            return null;
        }

        $test = str_starts_with((string) config('cashier.secret'), 'sk_test_') ? 'test/' : '';

        return "https://dashboard.stripe.com/{$test}customers/{$customerId}";
    }
}

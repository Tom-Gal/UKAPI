<?php

namespace Tests\Feature;

use App\Domain\Health\HealthIncidentRecorder;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\AdminAuditEvent;
use App\Models\DatasetSnapshot;
use App\Models\DatasetSnapshotHead;
use App\Models\ServiceHealthCheck;
use App\Models\ServiceHealthIncident;
use App\Models\User;
use App\Notifications\ApiKeyActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_page_reports_aggregate_health_without_exposing_request_or_job_payloads(): void
    {
        $this->travelTo('2026-10-26 12:00:00');
        config()->set('ukapi.free_plan.burst_requests_per_second', 100);
        [$customer, $token] = $this->key();
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $sensitiveAmount = '98765.43';

        $headers = ['Authorization' => "Bearer {$token}"];
        $this->getJson("/v1/vat/calculate?amount={$sensitiveAmount}&rate=20", $headers)->assertOk();
        $this->getJson('/v1/vat/calculate?amount=100&rate=invalid', $headers)
            ->assertUnprocessable()->assertJsonPath('error.code', 'invalid_parameter');

        DB::table('daily_usage_rejections')->insert([
            'user_id' => $customer->id,
            'date' => now()->toDateString(),
            'reason' => 'rate_limit',
            'requests' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => 'private pending payload',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->subMinute()->timestamp,
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => '019a26db-9f64-71d7-a4dc-failed000001',
            'connection' => 'database',
            'queue' => 'mail',
            'payload' => 'private failed payload',
            'exception' => 'private exception message',
            'failed_at' => now(),
        ]);
        $snapshot = DatasetSnapshot::query()->create([
            'dataset' => 'crime',
            'scope_key' => 'uk',
            'version' => '01K8H4B6TS0000000000000000',
            'source' => 'police_uk',
            'captured_at' => now()->subHours(3),
            'source_updated_at' => now()->subDay(),
            'checksum' => str_repeat('a', 64),
            'record_count' => 12,
            'payload' => ['private' => 'snapshot payload'],
        ]);
        DatasetSnapshotHead::query()->create([
            'dataset' => 'crime',
            'scope_key' => 'uk',
            'snapshot_id' => $snapshot->id,
            'switched_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get(route('admin.operations'));
        $response->assertOk()
            ->assertJsonPath('component', 'admin/operations')
            ->assertJsonPath('props.endpointMetrics.0.endpoint', 'vat.calculate')
            ->assertJsonPath('props.endpointMetrics.0.requests', 2)
            ->assertJsonPath('props.endpointMetrics.0.errors', 1)
            ->assertJsonPath('props.hotspots.0.user_id', $customer->id)
            ->assertJsonPath('props.hotspots.0.rate_limit_rejections', 4)
            ->assertJsonPath('props.queueHealth.pending', 1)
            ->assertJsonPath('props.queueHealth.failed', 1)
            ->assertJsonPath('props.queueHealth.recent_failures.0.queue', 'mail')
            ->assertJsonPath('props.snapshots.0.version', $snapshot->version)
            ->assertJsonPath('props.snapshots.0.record_count', 12);

        foreach ([$sensitiveAmount, $token, 'private pending payload', 'private failed payload', 'private exception message', 'snapshot payload'] as $private) {
            $this->assertStringNotContainsString($private, $response->getContent());
        }

        $metric = DB::table('endpoint_daily_metrics')->where('endpoint', 'vat.calculate')->get();
        $this->assertSame(2, (int) $metric->sum('requests'));
        $this->assertSame(['2xx', '4xx'], $metric->pluck('status_family')->sort()->values()->all());
        $this->travelBack();
    }

    public function test_health_incidents_are_grouped_recovered_and_acknowledged_with_an_audit_event(): void
    {
        $this->travelTo('2026-10-26 12:00:00');
        $recorder = app(HealthIncidentRecorder::class);
        $check = ServiceHealthCheck::query()->create([
            'service' => 'postcodes',
            'status' => 'unavailable',
            'failure_reason' => 'provider_timeout',
            'consecutive_failures' => 1,
            'latency_ms' => 750,
            'last_checked_at' => now(),
        ]);

        $recorder->record($check);
        $check->forceFill(['consecutive_failures' => 2, 'latency_ms' => 900])->save();
        $recorder->record($check);

        $incident = ServiceHealthIncident::query()->firstOrFail();
        $this->assertSame(2, $incident->occurrences);
        $this->assertSame(900, $incident->latest_latency_ms);
        $this->assertNull($incident->recovered_at);

        $admin = User::factory()->admin()->withTwoFactor()->create();
        $this->actingAs($admin)->post(route('admin.incidents.acknowledge', $incident), [
            'note' => 'Investigating provider timeouts with the source operator.',
        ])->assertRedirect(route('admin.operations'));

        $incident->refresh();
        $this->assertSame($admin->id, $incident->acknowledged_by);
        $this->assertSame('Investigating provider timeouts with the source operator.', $incident->acknowledgement_note);
        $this->assertDatabaseHas('admin_audit_events', [
            'actor_user_id' => $admin->id,
            'action' => 'health_incident_acknowledged',
            'subject_id' => (string) $incident->id,
        ]);

        $check->forceFill(['status' => 'available', 'failure_reason' => null, 'latency_ms' => 120])->save();
        $recorder->record($check);
        $this->assertNotNull($incident->fresh()->recovered_at);
        $this->assertDatabaseCount('service_health_incidents', 1);
        $this->travelBack();
    }

    public function test_support_key_revocation_requires_admin_two_factor_and_recent_password_confirmation(): void
    {
        Notification::fake();
        [$owner] = $this->key();
        $key = $owner->apiKeys()->firstOrFail();
        $nonAdmin = User::factory()->create();
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $reason = 'Customer confirmed that this key may be compromised.';

        $this->actingAs($nonAdmin)->post(route('admin.api-keys.revoke', $key), ['reason' => $reason])
            ->assertForbidden();
        $this->actingAs($admin)->post(route('admin.api-keys.revoke', $key), ['reason' => $reason])
            ->assertRedirect(route('password.confirm'));
        $this->assertSame('active', $key->fresh()->status);

        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->timestamp])
            ->post(route('admin.api-keys.revoke', $key), ['reason' => $reason])
            ->assertRedirect(route('admin.users'));

        $this->assertSame('revoked', $key->fresh()->status);
        $this->assertDatabaseHas('api_key_audit_events', [
            'api_key_id' => $key->id,
            'actor_user_id' => $admin->id,
            'action' => 'revoked',
        ]);
        $this->assertDatabaseHas('admin_audit_events', [
            'actor_user_id' => $admin->id,
            'action' => 'api_key_revoked',
            'reason' => $reason,
        ]);
        Notification::assertSentTo($owner, ApiKeyActivityNotification::class);
    }

    public function test_admin_users_show_local_subscription_state_and_a_validated_stripe_customer_link(): void
    {
        config()->set('cashier.secret', 'sk_test_example');
        config()->set('ukapi.paid_plans.pro.stripe_price', 'price_pro_test');
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $customer = User::factory()->create(['stripe_id' => 'cus_safe123']);
        $customer->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_safe123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_test',
        ]);

        $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()))
            ->get(route('admin.users'))
            ->assertOk()
            ->assertJsonPath('component', 'admin/users')
            ->assertJsonPath('props.users.0.id', $customer->id)
            ->assertJsonPath('props.users.0.plan', 'Pro')
            ->assertJsonPath('props.users.0.subscription.status', 'active')
            ->assertJsonPath('props.users.0.subscription.stripe_id', 'sub_safe123')
            ->assertJsonPath('props.users.0.stripe_url', 'https://dashboard.stripe.com/test/customers/cus_safe123');
    }

    public function test_notification_delivery_events_store_only_safe_aggregate_failure_details(): void
    {
        $user = User::factory()->create();
        $key = $user->apiKeys()->create([
            'public_id' => 'k_notifytest',
            'secret_hash' => Hash::make('secret'),
            'name' => 'Notification test',
            'environment' => 'test',
        ]);
        $notification = ApiKeyActivityNotification::created($key);

        Event::dispatch(new NotificationSent($user, $notification, 'mail', null));
        Event::dispatch(new NotificationFailed($user, $notification, 'mail', [
            'exception' => new RuntimeException('private transport response'),
        ]));

        $this->assertDatabaseHas('notification_delivery_events', [
            'user_id' => $user->id,
            'notification' => 'ApiKeyActivityNotification',
            'channel' => 'mail',
            'status' => 'sent',
            'failure_type' => null,
        ]);
        $this->assertDatabaseHas('notification_delivery_events', [
            'user_id' => $user->id,
            'notification' => 'ApiKeyActivityNotification',
            'channel' => 'mail',
            'status' => 'failed',
            'failure_type' => RuntimeException::class,
        ]);
        $this->assertFalse(AdminAuditEvent::query()->where('reason', 'like', '%private transport response%')->exists());
        $this->assertSame(0, DB::table('notification_delivery_events')
            ->where('failure_type', 'like', '%private transport response%')->count());
    }

    /** @return array{User, string} */
    private function key(): array
    {
        $user = User::factory()->create();
        $secret = Str::lower(Str::random(43));
        $key = $user->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Operations test',
            'environment' => 'test',
        ]);

        return [$user, "uk_test_{$key->public_id}.{$secret}"];
    }
}

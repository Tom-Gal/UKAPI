<?php

namespace Tests\Feature;

use App\Domain\Health\HealthAlert;
use App\Domain\Operations\LoginAlertDecider;
use App\Models\ApiKey;
use App\Models\User;
use App\Notifications\ApiKeyActivityNotification;
use App\Notifications\ApiKeyRotationReminderNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\SubscriptionNotification;
use App\Notifications\UsageLimitNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('queuedNotificationProvider')]
    public function test_mail_notifications_have_bounded_queue_policy(string $case, string $expectedQueue): void
    {
        config()->set([
            'ukapi.notifications.critical_queue' => 'mail-critical',
            'ukapi.notifications.default_queue' => 'mail',
            'ukapi.operations.queue_worker_enabled' => true,
            'queue.default' => 'database',
        ]);
        $user = User::factory()->create();
        $key = $user->apiKeys()->create([
            'public_id' => 'queue-policy-key',
            'secret_hash' => hash('sha256', 'queue-policy-secret'),
            'name' => 'Queue policy',
            'environment' => 'test',
            'status' => 'active',
        ]);
        $notification = $this->notification($case, $key);

        $this->assertInstanceOf(ShouldQueueAfterCommit::class, $notification);
        $this->assertSame(4, $notification->tries);
        $this->assertSame(45, $notification->timeout);
        $this->assertSame([60, 300, 900], $notification->backoff());
        $this->assertSame(['mail' => $expectedQueue], $notification->viaQueues());
        $this->assertSame(['mail' => 'database'], $notification->viaConnections());
    }

    /** @return array<string, array{string, string}> */
    public static function queuedNotificationProvider(): array
    {
        return [
            'email verification is critical' => ['verify', 'mail-critical'],
            'password reset is critical' => ['reset', 'mail-critical'],
            'security activity is critical' => ['security', 'mail-critical'],
            'API key activity is critical' => ['api_key', 'mail-critical'],
            'provider health is critical' => ['health', 'mail-critical'],
            'subscription lifecycle is routine' => ['subscription', 'mail'],
            'usage limit is routine' => ['usage', 'mail'],
            'rotation reminder is routine' => ['rotation', 'mail'],
        ];
    }

    public function test_notification_dispatch_pushes_a_priority_queue_job_instead_of_sending_mail_inline(): void
    {
        Queue::fake();
        config()->set([
            'queue.default' => 'database',
            'ukapi.operations.queue_worker_enabled' => true,
        ]);
        $user = User::factory()->create();

        $user->notify(SecurityAlertNotification::passwordChanged());

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job): bool {
            return $job->notification instanceof SecurityAlertNotification
                && $job->queue === 'mail-critical'
                && $job->tries === 4
                && $job->timeout === 45
                && $job->afterCommit === true;
        });
    }

    public function test_api_key_request_only_enqueues_its_mail_boundary(): void
    {
        Queue::fake();
        config()->set([
            'queue.default' => 'database',
            'ukapi.operations.queue_worker_enabled' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('api-keys.store'), [
            'name' => 'Production server',
            'environment' => 'live',
        ])->assertRedirect(route('api-keys.index'))
            ->assertSessionHas('api_key_created.token');

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof ApiKeyActivityNotification
            && $job->queue === 'mail-critical');
    }

    public function test_familiar_sign_in_fingerprint_is_deduplicated_without_suppressing_a_new_device(): void
    {
        Cache::flush();
        config()->set('ukapi.notifications.login_deduplication_days', 30);
        $user = User::factory()->create();
        $decider = app(LoginAlertDecider::class);

        $this->assertTrue($decider->shouldNotify($user, '203.0.113.10', 'Example Browser'));
        $this->assertFalse($decider->shouldNotify($user, '203.0.113.10', 'Example Browser'));
        $this->assertTrue($decider->shouldNotify($user, '203.0.113.11', 'Example Browser'));
    }

    public function test_mail_falls_back_to_sync_until_the_cloud_worker_is_explicitly_enabled(): void
    {
        config()->set([
            'queue.default' => 'database',
            'ukapi.operations.queue_worker_enabled' => false,
        ]);

        $notification = SecurityAlertNotification::passwordChanged();

        $this->assertSame(['mail' => 'sync'], $notification->viaConnections());
    }

    private function notification(string $case, ApiKey $key): Notification
    {
        return match ($case) {
            'verify' => new VerifyEmailNotification,
            'reset' => new ResetPasswordNotification('test-token'),
            'security' => SecurityAlertNotification::twoFactorEnabled(),
            'api_key' => ApiKeyActivityNotification::created($key),
            'health' => new HealthAlert('postcodes', false, 'probe_failed'),
            'subscription' => SubscriptionNotification::confirmed('Pro'),
            'usage' => new UsageLimitNotification(800, 1000, 80, now()->addMonth()->timestamp),
            'rotation' => new ApiKeyRotationReminderNotification([[
                'name' => 'Production server',
                'environment' => 'Live',
                'ageDays' => 90,
            ]]),
            default => throw new \InvalidArgumentException('Unknown notification test case.'),
        };
    }
}

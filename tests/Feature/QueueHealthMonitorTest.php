<?php

namespace Tests\Feature;

use App\Notifications\QueueHealthAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class QueueHealthMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Notification::fake();
        config()->set([
            'queue.default' => 'database',
            'ukapi.operations.queue_monitor.enabled' => true,
            'ukapi.operations.queue_monitor.max_pending' => 100,
            'ukapi.operations.queue_monitor.max_oldest_seconds' => 300,
            'ukapi.operations.queue_monitor.repeat_after_minutes' => 60,
            'health.alerts.email' => 'operator@example.com',
        ]);
    }

    public function test_queue_alarm_is_the_only_synchronous_mail_boundary(): void
    {
        $this->assertNotInstanceOf(ShouldQueue::class, new QueueHealthAlert(false, 1, 0, 301));
    }

    public function test_failed_job_alert_is_deduplicated_and_recovery_is_reported(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => 'failed-job-uuid',
            'connection' => 'database',
            'queue' => 'mail',
            'payload' => '{}',
            'exception' => 'Private exception content is not included in the alert.',
            'failed_at' => now(),
        ]);

        $this->artisan('ukapi:queue:monitor')->assertFailed();
        Notification::assertSentOnDemand(QueueHealthAlert::class, fn (QueueHealthAlert $alert): bool => ! $alert->recovered
            && $alert->failed === 1);

        $this->artisan('ukapi:queue:monitor')->assertFailed();
        Notification::assertCount(1);

        DB::table('failed_jobs')->delete();
        $this->artisan('ukapi:queue:monitor')->assertSuccessful();
        Notification::assertSentOnDemand(QueueHealthAlert::class, fn (QueueHealthAlert $alert): bool => $alert->recovered);
        Notification::assertCount(2);
    }

    public function test_stale_pending_job_crosses_the_backlog_age_threshold(): void
    {
        DB::table('jobs')->insert([
            'queue' => 'mail',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subMinutes(6)->getTimestamp(),
            'created_at' => now()->subMinutes(6)->getTimestamp(),
        ]);

        $this->artisan('ukapi:queue:monitor')->assertFailed();
        Notification::assertSentOnDemand(QueueHealthAlert::class, fn (QueueHealthAlert $alert): bool => $alert->oldestAgeSeconds !== null
            && $alert->oldestAgeSeconds >= 360);
    }
}

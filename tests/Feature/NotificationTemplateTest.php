<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ApiKeyActivityNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\UsageLimitNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_notifications_render_the_branded_ui_template(): void
    {
        $user = User::factory()->create(['name' => 'Alex Smith']);
        $key = $user->apiKeys()->create([
            'public_id' => 'k_testkey',
            'secret_hash' => 'not-a-real-secret',
            'name' => 'Local development',
            'environment' => 'test',
        ]);
        $notifications = [
            new VerifyEmailNotification,
            new ResetPasswordNotification('test-reset-token'),
            SecurityAlertNotification::twoFactorEnabled(),
            new UsageLimitNotification(used: 4_000, limit: 5_000, percentage: 80, resetsAt: now()->addMonth()->timestamp),
            ApiKeyActivityNotification::created($key),
            ApiKeyActivityNotification::revoked($key),
        ];

        foreach ($notifications as $notification) {
            $html = $notification->toMail($user)->render();

            $this->assertStringContainsString('UKAPI<span style="color:#1248e8;">.io</span>', $html);
            $this->assertStringContainsString('background:#10182d', $html);
            $this->assertStringContainsString('Alex Smith', $html);
            $this->assertStringNotContainsString('color-scheme" content="dark', $html);
        }
    }
}

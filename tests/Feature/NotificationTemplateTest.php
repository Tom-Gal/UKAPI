<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SecurityAlertNotification;
use App\Notifications\UsageLimitNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_notifications_render_the_branded_light_template(): void
    {
        $user = User::factory()->create(['name' => 'Alex Smith']);
        $notifications = [
            new VerifyEmailNotification,
            new ResetPasswordNotification('test-reset-token'),
            SecurityAlertNotification::twoFactorEnabled(),
            new UsageLimitNotification(used: 4_000, limit: 5_000, percentage: 80, resetsAt: now()->addMonth()->timestamp),
        ];

        foreach ($notifications as $notification) {
            $html = $notification->toMail($user)->render();

            $this->assertStringContainsString('UK<span style="color:#2563eb;">API</span>', $html);
            $this->assertStringContainsString('Alex Smith', $html);
            $this->assertStringNotContainsString('color-scheme" content="dark', $html);
        }
    }
}

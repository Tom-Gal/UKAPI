<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ApiKeyRotationReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiKeyRotationReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reminds_customers_about_active_keys_after_ninety_days(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $key = $user->apiKeys()->create([
            'public_id' => 'k_rotation_test',
            'secret_hash' => 'not-a-real-secret',
            'name' => 'Production key',
            'environment' => 'live',
            'status' => 'active',
        ]);
        $key->forceFill(['created_at' => now()->subDays(91)])->save();

        $this->artisan('ukapi:remind-key-rotation')
            ->expectsOutput('Sent 1 API key rotation reminder(s).')
            ->assertSuccessful();

        Notification::assertSentTo($user, ApiKeyRotationReminderNotification::class);
        $this->assertNotNull($key->fresh()->last_rotation_reminded_at);
    }

    public function test_it_does_not_remind_again_before_ninety_days_have_elapsed(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $key = $user->apiKeys()->create([
            'public_id' => 'k_rotation_recent',
            'secret_hash' => 'not-a-real-secret',
            'name' => 'Production key',
            'environment' => 'live',
            'status' => 'active',
            'last_rotation_reminded_at' => now()->subDays(89),
        ]);
        $key->forceFill(['created_at' => now()->subDays(180)])->save();

        $this->artisan('ukapi:remind-key-rotation')
            ->expectsOutput('Sent 0 API key rotation reminder(s).')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }
}

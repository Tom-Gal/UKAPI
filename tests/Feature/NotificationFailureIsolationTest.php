<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class NotificationFailureIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_succeeds_when_security_mail_delivery_fails(): void
    {
        $this->bindFailingNotificationDispatcher();
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_api_key_creation_succeeds_and_shows_the_secret_when_mail_delivery_fails(): void
    {
        $this->bindFailingNotificationDispatcher();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('api-keys.store'), [
            'name' => 'Production server',
            'environment' => 'live',
        ]);

        $response->assertRedirect(route('api-keys.index'))
            ->assertSessionHas('api_key_created.token');
        $this->assertDatabaseHas('api_keys', [
            'user_id' => $user->id,
            'name' => 'Production server',
            'status' => 'active',
        ]);
    }

    public function test_password_update_succeeds_when_security_mail_delivery_fails(): void
    {
        $this->bindFailingNotificationDispatcher();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('security.edit'))
            ->put(route('user-password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('security.edit'));

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    private function bindFailingNotificationDispatcher(): void
    {
        $this->app->instance(Dispatcher::class, new class implements Dispatcher
        {
            public function send($notifiables, $notification): void
            {
                throw new RuntimeException('Simulated private mail transport failure.');
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new RuntimeException('Simulated private mail transport failure.');
            }
        });
    }
}

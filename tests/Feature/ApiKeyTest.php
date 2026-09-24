<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_user_can_create_a_test_key_and_only_receives_the_secret_once(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('api-keys.store'), [
            'name' => 'Local development',
            'environment' => 'test',
        ]);

        $key = ApiKey::firstOrFail();
        $response->assertRedirect(route('api-keys.index'));
        $response->assertSessionHas('api_key_created', function (array $created) use ($key): bool {
            [, $secret] = explode('.', $created['token']);

            return $created['name'] === 'Local development'
                && str_starts_with($created['token'], 'uk_test_'.$key->public_id.'.')
                && Hash::check($secret, $key->secret_hash);
        });
        $this->assertDatabaseMissing('api_keys', ['secret_hash' => $response->getSession()->get('api_key_created.token')]);
        $this->assertDatabaseHas('api_key_audit_events', [
            'api_key_id' => $key->id,
            'actor_user_id' => $user->id,
            'action' => 'created',
        ]);
    }

    public function test_a_user_can_revoke_their_own_key(): void
    {
        $user = User::factory()->create();
        $key = $user->apiKeys()->create([
            'public_id' => 'k_localtest',
            'secret_hash' => Hash::make('secret'),
            'name' => 'Local development',
            'environment' => 'test',
        ]);

        $this->actingAs($user)->delete(route('api-keys.destroy', $key))
            ->assertRedirect(route('api-keys.index'));

        $this->assertSame('revoked', $key->fresh()->status);
        $this->assertNotNull($key->fresh()->revoked_at);
        $this->assertDatabaseHas('api_key_audit_events', ['api_key_id' => $key->id, 'action' => 'revoked']);
    }

    public function test_a_user_cannot_revoke_another_users_key(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $key = $owner->apiKeys()->create([
            'public_id' => 'k_ownerkey',
            'secret_hash' => Hash::make('secret'),
            'name' => 'Owner key',
            'environment' => 'live',
        ]);

        $this->actingAs($otherUser)->delete(route('api-keys.destroy', $key))->assertNotFound();
        $this->assertSame('active', $key->fresh()->status);
    }

    public function test_admin_routes_require_an_explicit_admin_role(): void
    {
        $user = User::factory()->create();
        $adminWithoutTwoFactor = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('admin.overview'))->assertForbidden();
        $this->actingAs($adminWithoutTwoFactor)
            ->get(route('admin.overview'))
            ->assertRedirect(route('security.edit'));
        $this->actingAs(User::factory()->admin()->withTwoFactor()->create())
            ->get(route('admin.overview'))
            ->assertOk();
    }
}

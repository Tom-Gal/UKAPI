<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\User;
use App\Notifications\ApiKeyActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_user_can_create_a_test_key_and_only_receives_the_secret_once(): void
    {
        Notification::fake();
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
        Notification::assertSentTo($user, ApiKeyActivityNotification::class);
    }

    public function test_a_user_can_revoke_their_own_key(): void
    {
        Notification::fake();
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
        Notification::assertSentTo($user, ApiKeyActivityNotification::class);
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

    public function test_a_scoped_key_is_limited_to_selected_endpoint_families_and_tracks_approximate_use(): void
    {
        $this->travelTo('2026-10-26 12:34:56');
        [$key, $token] = $this->key(['vat']);

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();
        $this->getJson('/v1/postcodes/SW1A1AA', [
            'Authorization' => "Bearer {$token}",
        ])->assertForbidden()->assertJsonPath('error.code', 'api_key_scope_forbidden');

        $key->refresh();
        $this->assertSame('vat', $key->last_used_endpoint_family);
        $this->assertSame('2026-10-26 12:00:00', $key->last_used_at?->utc()->format('Y-m-d H:i:s'));
        $this->travelBack();
    }

    public function test_ip_allow_lists_accept_cidr_ranges_and_reject_other_addresses(): void
    {
        [$key, $token] = $this->key(null, ['203.0.113.0/24']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->getJson('/v1/vat/calculate?amount=100&rate=20', [
                'Authorization' => "Bearer {$token}",
            ])->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])
            ->getJson('/v1/vat/calculate?amount=100&rate=20', [
                'Authorization' => "Bearer {$token}",
            ])->assertForbidden()->assertJsonPath('error.code', 'api_key_ip_not_allowed');

        $this->assertSame(['203.0.113.0/24'], $key->ip_allow_list);
    }

    public function test_a_user_can_update_key_scopes_and_the_change_is_audited(): void
    {
        [$key] = $this->key();

        $this->actingAs($key->user)->patch(route('api-keys.update', $key), [
            'scopes' => ['vat', 'companies'],
        ])->assertRedirect(route('api-keys.index'));

        $this->assertSame(['vat', 'companies'], $key->fresh()->scopes);
        $this->assertDatabaseHas('api_key_audit_events', [
            'api_key_id' => $key->id,
            'action' => 'policy_updated',
        ]);
    }

    public function test_rotation_creates_a_one_time_replacement_and_expires_the_old_key_after_overlap(): void
    {
        Notification::fake();
        $this->travelTo('2026-10-26 12:00:00');
        [$key, $oldToken] = $this->key(['vat']);

        $response = $this->actingAs($key->user)->post(route('api-keys.rotate', $key), [
            'overlap_hours' => 1,
        ]);
        $response->assertRedirect(route('api-keys.index'))
            ->assertSessionHas('api_key_created', fn (array $created): bool => $created['activity'] === 'rotated'
                && $created['overlap_hours'] === 1
                && str_starts_with($created['token'], 'uk_test_'));
        $replacementToken = $response->getSession()->get('api_key_created.token');
        $this->assertIsString($replacementToken);
        $replacement = ApiKey::query()->where('rotated_from_id', $key->id)->firstOrFail();
        $this->assertSame(['vat'], $replacement->scopes);
        $this->assertSame('2026-10-26 13:00:00', $key->fresh()->expires_at?->utc()->format('Y-m-d H:i:s'));

        $this->actingAs($key->user)->get(route('api-keys.index'))->assertOk();
        $this->actingAs($key->user)->get(route('api-keys.index'))
            ->assertSessionMissing('api_key_created');

        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$oldToken}",
        ])->assertOk();
        $this->travel(61)->minutes();
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$oldToken}",
        ])->assertUnauthorized()->assertJsonPath('error.code', 'api_key_revoked');
        $this->getJson('/v1/vat/calculate?amount=100&rate=20', [
            'Authorization' => "Bearer {$replacementToken}",
        ])->assertOk();

        $this->assertDatabaseHas('api_key_audit_events', ['api_key_id' => $key->id, 'action' => 'rotated']);
        $this->assertDatabaseHas('api_key_audit_events', ['api_key_id' => $replacement->id, 'action' => 'rotation_created']);
        $this->travelBack();
    }

    public function test_free_plan_cannot_add_an_ip_allow_list_and_security_export_contains_no_secret_hash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('api-keys.store'), [
            'name' => 'Restricted key',
            'environment' => 'live',
            'ip_allow_list' => '203.0.113.10',
        ])->assertSessionHasErrors('ip_allow_list');
        $this->assertDatabaseCount('api_keys', 0);

        [$key] = $this->key();
        $key->auditEvents()->create([
            'actor_user_id' => $key->user_id,
            'action' => 'created',
        ]);
        $response = $this->actingAs($key->user)->get(route('api-keys.security-export'));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString($key->public_id, $csv);
        $this->assertStringNotContainsString($key->secret_hash, $csv);
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

    /** @param list<string>|null $scopes
     * @param  list<string>|null  $ipAllowList
     * @return array{ApiKey, string}
     */
    private function key(?array $scopes = null, ?array $ipAllowList = null): array
    {
        $secret = Str::lower(Str::random(43));
        $key = User::factory()->create()->apiKeys()->create([
            'public_id' => 'k_'.Str::lower(Str::random(10)),
            'secret_hash' => Hash::make($secret),
            'name' => 'Policy key',
            'environment' => 'test',
            'scopes' => $scopes,
            'ip_allow_list' => $ipAllowList,
        ]);

        return [$key, "uk_test_{$key->public_id}.{$secret}"];
    }
}

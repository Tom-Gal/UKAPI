<?php

namespace App\Domain\ApiKeys;

use App\Models\ApiKey;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ApiKeyManager
{
    /**
     * @param  list<string>|null  $scopes
     * @param  list<string>  $ipAllowList
     * @return array{key: ApiKey, token: string}
     */
    public function issue(
        User $user,
        string $name,
        string $environment,
        ?array $scopes,
        array $ipAllowList,
        ?string $requestId,
        ?ApiKey $rotatedFrom = null,
    ): array {
        $publicId = $this->uniquePublicId();
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $key = $user->apiKeys()->create([
            'public_id' => $publicId,
            'secret_hash' => Hash::make($secret),
            'name' => $name,
            'environment' => $environment,
            'scopes' => $scopes,
            'ip_allow_list' => $ipAllowList === [] ? null : $ipAllowList,
            'rotated_from_id' => $rotatedFrom?->id,
        ]);
        $key->auditEvents()->create([
            'actor_user_id' => $user->id,
            'action' => $rotatedFrom ? 'rotation_created' : 'created',
            'request_id' => $requestId,
            'details' => [
                'scope_count' => $scopes === null ? null : count($scopes),
                'ip_allow_list_count' => count($ipAllowList),
            ],
        ]);

        return [
            'key' => $key,
            'token' => "uk_{$environment}_{$publicId}.{$secret}",
        ];
    }

    /** @return array{key: ApiKey, token: string} */
    public function rotate(ApiKey $apiKey, User $actor, int $overlapHours, ?string $requestId): array
    {
        return DB::transaction(function () use ($apiKey, $actor, $overlapHours, $requestId): array {
            $replacement = $this->issue(
                user: $actor,
                name: $apiKey->name,
                environment: $apiKey->environment,
                scopes: $apiKey->scopes,
                ipAllowList: $apiKey->ip_allow_list ?? [],
                requestId: $requestId,
                rotatedFrom: $apiKey,
            );
            $expiresAt = CarbonImmutable::now('UTC')->addHours($overlapHours);
            $updates = ['expires_at' => $expiresAt];
            if ($overlapHours === 0) {
                $updates['status'] = 'revoked';
                $updates['revoked_at'] = $expiresAt;
            }
            $apiKey->forceFill($updates)->save();
            $apiKey->auditEvents()->create([
                'actor_user_id' => $actor->id,
                'action' => 'rotated',
                'request_id' => $requestId,
                'details' => [
                    'overlap_hours' => $overlapHours,
                    'replacement_prefix' => $this->prefix($replacement['key']),
                ],
            ]);

            return $replacement;
        }, 3);
    }

    /** @param list<string>|null $scopes
     * @param  list<string>  $ipAllowList
     */
    public function updatePolicy(
        ApiKey $apiKey,
        User $actor,
        ?array $scopes,
        array $ipAllowList,
        ?string $requestId,
    ): void {
        $apiKey->forceFill([
            'scopes' => $scopes,
            'ip_allow_list' => $ipAllowList === [] ? null : $ipAllowList,
        ])->save();
        $apiKey->auditEvents()->create([
            'actor_user_id' => $actor->id,
            'action' => 'policy_updated',
            'request_id' => $requestId,
            'details' => [
                'scope_count' => $scopes === null ? null : count($scopes),
                'ip_allow_list_count' => count($ipAllowList),
            ],
        ]);
    }

    public function revoke(ApiKey $apiKey, User $actor, ?string $requestId): bool
    {
        if ($apiKey->status !== 'active') {
            return false;
        }

        $apiKey->forceFill(['status' => 'revoked', 'revoked_at' => now()])->save();
        $apiKey->auditEvents()->create([
            'actor_user_id' => $actor->id,
            'action' => 'revoked',
            'request_id' => $requestId,
        ]);

        return true;
    }

    public function prefix(ApiKey $key): string
    {
        return "uk_{$key->environment}_{$key->public_id}";
    }

    private function uniquePublicId(): string
    {
        do {
            $publicId = 'k_'.Str::lower(Str::random(10));
        } while (ApiKey::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }
}

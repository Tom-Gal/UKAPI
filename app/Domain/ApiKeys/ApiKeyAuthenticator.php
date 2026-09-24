<?php

namespace App\Domain\ApiKeys;

use App\Models\ApiKey;
use App\Support\Api\ApiException;
use Illuminate\Support\Facades\Hash;

final class ApiKeyAuthenticator
{
    public function authenticate(?string $bearerToken): ApiKey
    {
        $token = ApiKeyToken::fromBearer($bearerToken);
        $apiKey = ApiKey::query()->where('public_id', $token->publicId)->first();

        if (! $apiKey instanceof ApiKey || ! Hash::check($token->secret, $apiKey->secret_hash)) {
            throw ApiException::invalidApiKey();
        }

        if ($apiKey->environment !== $token->environment) {
            throw ApiException::invalidApiKey();
        }

        if ($apiKey->status === 'revoked') {
            throw ApiException::revokedApiKey();
        }

        if ($apiKey->status !== 'active') {
            throw ApiException::invalidApiKey();
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        return $apiKey;
    }
}

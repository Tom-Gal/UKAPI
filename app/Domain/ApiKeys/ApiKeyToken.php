<?php

namespace App\Domain\ApiKeys;

use App\Support\Api\ApiException;

final readonly class ApiKeyToken
{
    private function __construct(
        public string $environment,
        public string $publicId,
        public string $secret,
    ) {}

    public static function fromBearer(?string $token): self
    {
        if (! is_string($token)) {
            throw ApiException::invalidApiKey();
        }

        $matches = [];

        if (! preg_match('/^uk_(live|test)_(k_[a-z0-9]{10})\.([A-Za-z0-9_-]{32,})$/', $token, $matches)) {
            throw ApiException::invalidApiKey();
        }

        return new self(
            environment: $matches[1],
            publicId: $matches[2],
            secret: $matches[3],
        );
    }
}

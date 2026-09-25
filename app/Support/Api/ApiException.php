<?php

namespace App\Support\Api;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public readonly string $apiCode,
        string $message,
        public readonly int $status,
        public readonly array $details = [],
        public readonly array $headers = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function invalidApiKey(): self
    {
        return new self(
            apiCode: 'invalid_api_key',
            message: 'The API key is invalid.',
            status: 401,
        );
    }

    public static function revokedApiKey(): self
    {
        return new self(
            apiCode: 'api_key_revoked',
            message: 'The API key has been revoked.',
            status: 401,
        );
    }

    public static function rateLimitExceeded(int $retryAfter, int $limit): self
    {
        return new self(
            apiCode: 'rate_limit_exceeded',
            message: 'Too many requests. Please retry shortly.',
            status: 429,
            headers: [
                'RateLimit-Limit' => (string) $limit,
                'RateLimit-Remaining' => '0',
                'RateLimit-Reset' => (string) (now()->addSeconds($retryAfter)->getTimestamp()),
                'Retry-After' => (string) $retryAfter,
            ],
        );
    }

    public static function quotaExceeded(int $quota, int $resetAt): self
    {
        return new self(
            apiCode: 'quota_exceeded',
            message: 'The monthly request quota has been reached.',
            status: 429,
            headers: [
                'X-Quota-Limit' => (string) $quota,
                'X-Quota-Remaining' => '0',
                'X-Quota-Reset' => (string) $resetAt,
            ],
        );
    }

    public static function invalidPostcode(): self
    {
        return new self(
            apiCode: 'invalid_postcode',
            message: 'The postcode must be a valid UK postcode.',
            status: 422,
        );
    }

    public static function invalidParameter(string $field, string $message): self
    {
        return new self(
            apiCode: 'invalid_parameter',
            message: $message,
            status: 422,
            details: ['fields' => [$field => [$message]]],
        );
    }

    public static function postcodeNotFound(): self
    {
        return new self(
            apiCode: 'postcode_not_found',
            message: 'No postcode record was found for the supplied postcode.',
            status: 404,
        );
    }

    public static function invalidCoordinates(): self
    {
        return new self(
            apiCode: 'invalid_coordinates',
            message: 'Latitude and longitude must be valid WGS84 coordinates.',
            status: 422,
        );
    }

    public static function invalidCompanyNumber(): self
    {
        return new self(
            apiCode: 'invalid_company_number',
            message: 'The company number must contain up to eight letters or digits.',
            status: 422,
        );
    }

    public static function companyNotFound(): self
    {
        return new self(
            apiCode: 'company_not_found',
            message: 'No company record was found for the supplied company number.',
            status: 404,
        );
    }

    public static function invalidSicCode(): self
    {
        return new self(
            apiCode: 'invalid_sic_code',
            message: 'The SIC code must contain exactly five digits.',
            status: 422,
        );
    }

    public static function sicNotFound(): self
    {
        return new self(
            apiCode: 'sic_not_found',
            message: 'No SIC record was found for the supplied code.',
            status: 404,
        );
    }

    public static function sicReferenceUnavailable(): self
    {
        return new self(
            apiCode: 'sic_reference_unavailable',
            message: 'The SIC reference snapshot is temporarily unavailable. Please retry shortly.',
            status: 503,
        );
    }

    public static function coordinatesNotFound(): self
    {
        return new self(
            apiCode: 'coordinates_not_found',
            message: 'No postcode record was found near the supplied coordinates.',
            status: 404,
        );
    }

    public static function providerUnavailable(): self
    {
        return new self(
            apiCode: 'upstream_unavailable',
            message: 'The upstream data provider is temporarily unavailable. Please retry shortly.',
            status: 503,
        );
    }

    public static function providerNotConfigured(): self
    {
        return new self(
            apiCode: 'upstream_unavailable',
            message: 'The requested data provider is not configured.',
            status: 503,
        );
    }

    public static function providerInvalidResponse(): self
    {
        return new self(
            apiCode: 'upstream_invalid_response',
            message: 'The upstream data provider returned an unexpected response.',
            status: 502,
        );
    }
}

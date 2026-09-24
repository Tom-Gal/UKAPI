<?php

return [

    'paths' => ['v1/*', 'openapi/*'],

    'allowed_methods' => ['GET', 'OPTIONS'],

    'allowed_origins' => (static function (): array {
        $origins = env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost:8000'));

        if (! is_string($origins)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $origins))));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Request-Id',
    ],

    'exposed_headers' => [
        'RateLimit-Limit',
        'RateLimit-Remaining',
        'RateLimit-Reset',
        'Retry-After',
        'X-Quota-Limit',
        'X-Quota-Remaining',
        'X-Quota-Reset',
        'X-Request-Id',
    ],

    'max_age' => 3600,

    'supports_credentials' => false,

];

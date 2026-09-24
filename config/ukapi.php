<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP TLS
    |--------------------------------------------------------------------------
    |
    | Production should use the host's maintained certificate store. The local
    | fallback lets the Windows PHP runtime verify public provider certificates
    | when it has no CA path configured in php.ini.
    |
    */

    'http_ca_bundle' => env(
        'HTTP_CA_BUNDLE',
        file_exists(storage_path('app/certificates/cacert.pem'))
            ? storage_path('app/certificates/cacert.pem')
            : null,
    ),

    /*
    |--------------------------------------------------------------------------
    | Default runtime entitlements
    |--------------------------------------------------------------------------
    |
    | Billing entitlements are resolved from Cashier's locally synchronized
    | Stripe subscription state. This keeps the API request path independent
    | of the Stripe API while still applying plan changes quickly after Stripe
    | sends a webhook.
    |
    */

    'free_plan' => [
        'key' => 'free',
        'label' => 'Free',
        'monthly_request_quota' => 5_000,
        'burst_requests_per_second' => 5,
    ],

    'paid_plans' => [
        'hobby' => [
            'label' => 'Hobby',
            'stripe_price' => env('STRIPE_PRICE_HOBBY'),
            'monthly_request_quota' => 50_000,
            'burst_requests_per_second' => 10,
        ],
        'pro' => [
            'label' => 'Pro',
            'stripe_price' => env('STRIPE_PRICE_PRO'),
            'monthly_request_quota' => 250_000,
            'burst_requests_per_second' => 25,
        ],
        'scale' => [
            'label' => 'Scale',
            'stripe_price' => env('STRIPE_PRICE_SCALE'),
            'monthly_request_quota' => 1_000_000,
            'burst_requests_per_second' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage-counter retention
    |--------------------------------------------------------------------------
    |
    | Counters are kept past the current month to allow dashboard reads and a
    | later durable roll-up job to reconcile them. The configured Valkey cache
    | is used for this hot path (Laravel retains "redis" as its driver name).
    |
    */

    'usage_counter_retention_days' => 35,

    /*
    |--------------------------------------------------------------------------
    | Usage notification thresholds
    |--------------------------------------------------------------------------
    |
    | A single alert is sent for each configured percentage in a calendar
    | month. The notification is deduplicated in Valkey alongside the usage
    | counters, so multiple API keys cannot produce duplicate account mail.
    |
    */

    'usage_notification_thresholds' => env('USAGE_NOTIFICATION_THRESHOLDS', '80,100'),

    /*
    |--------------------------------------------------------------------------
    | Postcodes.io provider
    |--------------------------------------------------------------------------
    */

    'postcodes_io' => [
        'base_url' => env('POSTCODES_IO_BASE_URL', 'https://api.postcodes.io'),
        'connect_timeout_seconds' => (int) env('POSTCODES_IO_CONNECT_TIMEOUT', 2),
        'timeout_seconds' => (int) env('POSTCODES_IO_TIMEOUT', 5),
        'cache_ttl_days' => (int) env('POSTCODES_IO_CACHE_TTL_DAYS', 30),
        'negative_cache_ttl_minutes' => (int) env('POSTCODES_IO_NEGATIVE_CACHE_TTL_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Companies House provider
    |--------------------------------------------------------------------------
    |
    | The API key is intentionally resolved from the environment only. Do not
    | persist it in application data or include it in request logs.
    |
    */

    'companies_house' => [
        'base_url' => env('COMPANIES_HOUSE_BASE_URL', 'https://api.company-information.service.gov.uk'),
        'api_key' => env('COMPANIES_HOUSE_API_KEY'),
        'user_agent' => env('COMPANIES_HOUSE_USER_AGENT', 'UKAPI.io/1.0'),
        'connect_timeout_seconds' => (int) env('COMPANIES_HOUSE_CONNECT_TIMEOUT', 2),
        'timeout_seconds' => (int) env('COMPANIES_HOUSE_TIMEOUT', 5),
        'profile_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_PROFILE_CACHE_TTL_MINUTES', 15),
        'officers_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_OFFICERS_CACHE_TTL_MINUTES', 60),
        'filings_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_FILINGS_CACHE_TTL_MINUTES', 15),
        'search_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_SEARCH_CACHE_TTL_MINUTES', 15),
        'stale_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_STALE_CACHE_TTL_MINUTES', 120),
        'negative_cache_ttl_minutes' => (int) env('COMPANIES_HOUSE_NEGATIVE_CACHE_TTL_MINUTES', 10),
        // Kept below the provider's published hard limit.
        'provider_request_limit' => (int) env('COMPANIES_HOUSE_PROVIDER_REQUEST_LIMIT', 500),
        'provider_limit_window_seconds' => (int) env('COMPANIES_HOUSE_PROVIDER_LIMIT_WINDOW_SECONDS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Companies House SIC 2007 reference snapshot
    |--------------------------------------------------------------------------
    |
    | The Companies House condensed SIC list is fetched by the explicit
    | `ukapi:sic:refresh` maintenance command, never on a customer request.
    | This gives code lookup/search stable local latency. A versioned seed is
    | bundled for first deployment; production refresh jobs should write their
    | current snapshot to the configured writable storage path.
    |
    */

    'sic_reference' => [
        'source_url' => env('SIC_REFERENCE_SOURCE_URL', 'https://resources.companieshouse.gov.uk/sic/'),
        'snapshot_path' => env('SIC_REFERENCE_SNAPSHOT_PATH', storage_path('app/reference/sic-2007.json')),
        'seed_snapshot_path' => resource_path('reference/sic-2007.json'),
        'cache_ttl_hours' => (int) env('SIC_REFERENCE_CACHE_TTL_HOURS', 24),
    ],

];

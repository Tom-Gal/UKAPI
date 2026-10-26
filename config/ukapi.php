<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public and operational hosts
    |--------------------------------------------------------------------------
    */

    'api_url' => env('UKAPI_API_URL', 'https://api.ukapi.co.uk'),

    'operations' => [
        // These values make Laravel Cloud's scheduler and worker expectations
        // explicit without trying to introspect host-level deployment state.
        'scheduler_enabled' => (bool) env('UKAPI_SCHEDULER_ENABLED', false),
        'queue_worker_enabled' => (bool) env('UKAPI_QUEUE_WORKER_ENABLED', false),
    ],

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
        'max_page_size' => 25,
        'features' => ['current_data'],
        'commercial_use' => false,
    ],

    'paid_plans' => [
        'hobby' => [
            'label' => 'Hobby',
            'stripe_price' => env('STRIPE_PRICE_HOBBY'),
            'annual_stripe_price' => env('STRIPE_PRICE_HOBBY_ANNUAL'),
            'monthly_price_pence' => 900,
            'annual_price_pence' => 9_000,
            'legacy_monthly_price_pence' => 499,
            'monthly_request_quota' => 50_000,
            'burst_requests_per_second' => 10,
            'max_page_size' => 50,
            'features' => ['current_data'],
            'commercial_use' => false,
        ],
        'pro' => [
            'label' => 'Pro',
            'stripe_price' => env('STRIPE_PRICE_PRO'),
            'annual_stripe_price' => env('STRIPE_PRICE_PRO_ANNUAL'),
            'monthly_price_pence' => 2_900,
            'annual_price_pence' => 29_000,
            'legacy_monthly_price_pence' => 1_299,
            'monthly_request_quota' => 250_000,
            'burst_requests_per_second' => 25,
            'max_page_size' => 100,
            'features' => ['current_data', 'historical_data', 'change_feeds', 'commercial_use', 'ip_allow_lists'],
            'commercial_use' => true,
        ],
        'scale' => [
            'label' => 'Scale',
            'stripe_price' => env('STRIPE_PRICE_SCALE'),
            'annual_stripe_price' => env('STRIPE_PRICE_SCALE_ANNUAL'),
            'monthly_price_pence' => 8_900,
            'annual_price_pence' => 89_000,
            'legacy_monthly_price_pence' => 2_999,
            'monthly_request_quota' => 1_000_000,
            'burst_requests_per_second' => 50,
            'max_page_size' => 100,
            'features' => ['current_data', 'historical_data', 'change_feeds', 'commercial_use', 'bulk_export', 'ip_allow_lists'],
            'commercial_use' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy usage-counter retention
    |--------------------------------------------------------------------------
    |
    | Retained for legacy cache migration compatibility. New usage is stored
    | in monthly_usage and monthly_endpoint_usage in the primary database.
    | Hard quotas never fall back to a cache or process-local counters.
    |
    */

    'usage_counter_retention_days' => 35,

    /*
    |--------------------------------------------------------------------------
    | Dataset snapshot retention
    |--------------------------------------------------------------------------
    */

    'snapshot_retention_days' => (int) env('SNAPSHOT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Dashboard usage labels
    |--------------------------------------------------------------------------
    |
    | These identifiers are the route defaults used by the API. Keeping the
    | labels here means the dashboard never records request parameters or data
    | returned by customers' API calls.
    |
    */

    'usage_endpoint_labels' => [
        'vat.calculate' => 'VAT calculations',
        'vat.remove' => 'VAT removal',
        'mot.registration' => 'MOT history by registration',
        'mot.vin' => 'MOT history by VIN',
        'companies.search' => 'Company searches',
        'companies.show' => 'Company profiles',
        'companies.officers' => 'Company officers',
        'companies.filings' => 'Company filings',
        'sic.search' => 'SIC searches',
        'sic.show' => 'SIC lookups',
        'sic.changes' => 'SIC changes',
        'sic.export' => 'SIC CSV exports',
        'crime.nearby' => 'Crime nearby',
        'crime.summary' => 'Crime summaries',
        'crime.categories' => 'Crime categories',
        'crime.changes' => 'Crime changes',
        'flood.warnings' => 'Flood warnings',
        'flood.nearby' => 'Flood nearby',
        'flood.stations.nearby' => 'Flood stations',
        'flood.stations.readings' => 'Flood readings',
        'flood.stations.changes' => 'Flood reading changes',
        'postcodes.validate' => 'Postcode validation',
        'postcodes.nearby' => 'Nearby postcodes',
        'postcodes.show' => 'Postcode lookups',
        'coordinates.postcode' => 'Coordinate lookups',
    ],

    /*
    |--------------------------------------------------------------------------
    | API key endpoint-family scopes
    |--------------------------------------------------------------------------
    |
    | Scopes use stable endpoint codes only. A null key scope means all current
    | and future endpoint families, which preserves existing-key behaviour.
    |
    */

    'api_endpoint_families' => [
        'vat' => ['label' => 'VAT utilities', 'endpoint_prefixes' => ['vat.']],
        'mot' => ['label' => 'MOT history', 'endpoint_prefixes' => ['mot.']],
        'companies' => ['label' => 'Companies House', 'endpoint_prefixes' => ['companies.']],
        'sic' => ['label' => 'SIC reference', 'endpoint_prefixes' => ['sic.']],
        'crime' => ['label' => 'Police and crime', 'endpoint_prefixes' => ['crime.']],
        'flood' => ['label' => 'Flood monitoring', 'endpoint_prefixes' => ['flood.']],
        'geography' => ['label' => 'Postcodes and coordinates', 'endpoint_prefixes' => ['postcodes.', 'coordinates.']],
    ],

    'api_key_rotation_max_overlap_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Usage notification thresholds
    |--------------------------------------------------------------------------
    |
    | A single alert is sent for each configured percentage in a calendar
    | month. The notification claim is stored durably in the primary database,
    | so multiple API keys cannot produce duplicate account mail.
    |
    */

    'usage_notification_thresholds' => env('USAGE_NOTIFICATION_THRESHOLDS', '80,100'),

    /*
    |--------------------------------------------------------------------------
    | API key authentication abuse control
    |--------------------------------------------------------------------------
    |
    | Key-scoped burst limits apply after authentication. This separate
    | per-IP limiter bounds database/hash work from credential guessing before
    | an API key can be identified.
    |
    */

    'api_auth_failure_limit' => (int) env('API_AUTH_FAILURE_LIMIT', 30),
    'api_auth_failure_window_seconds' => (int) env('API_AUTH_FAILURE_WINDOW_SECONDS', 60),

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
        'user_agent' => env('COMPANIES_HOUSE_USER_AGENT', 'UKApi.co.uk/1.0'),
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

    'mot_history' => [
        'base_url' => env('MOT_HISTORY_BASE_URL', 'https://history.mot.api.gov.uk'),
        'client_id' => env('MOT_HISTORY_CLIENT_ID'),
        'client_secret' => env('MOT_HISTORY_CLIENT_SECRET'),
        'scope' => env('MOT_HISTORY_SCOPE_URL', 'https://tapi.dvsa.gov.uk/.default'),
        'token_url' => env(
            'MOT_HISTORY_TOKEN_URL',
            'https://login.microsoftonline.com/a455b827-244f-4c97-b5b4-ce5d13b4d00c/oauth2/v2.0/token',
        ),
        'api_key' => env('MOT_HISTORY_API_KEY'),
        'access_token' => env('MOT_HISTORY_ACCESS_TOKEN'),
        'connect_timeout_seconds' => (int) env('MOT_HISTORY_CONNECT_TIMEOUT', 2),
        'timeout_seconds' => (int) env('MOT_HISTORY_TIMEOUT', 10),
        // DVSA publishes a 10-request burst limit. Keep one shared local budget
        // for customer traffic and scheduled health probes.
        'provider_request_limit' => (int) env('MOT_HISTORY_PROVIDER_REQUEST_LIMIT', 10),
        'provider_limit_window_seconds' => (int) env('MOT_HISTORY_PROVIDER_LIMIT_WINDOW_SECONDS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Police.uk provider
    |--------------------------------------------------------------------------
    |
    | Police.uk's public API requires no upstream credential. The local
    | limiter is still retained to protect both the source and our own retry
    | budget. Customer calls remain protected by their UKAPI API key.
    |
    */

    'police_uk' => [
        'base_url' => env('POLICE_UK_BASE_URL', 'https://data.police.uk/api'),
        'user_agent' => env('POLICE_UK_USER_AGENT', 'UKApi.co.uk/1.0'),
        'connect_timeout_seconds' => (int) env('POLICE_UK_CONNECT_TIMEOUT', 2),
        'timeout_seconds' => (int) env('POLICE_UK_TIMEOUT', 5),
        'crime_cache_ttl_hours' => (int) env('POLICE_UK_CRIME_CACHE_TTL_HOURS', 24),
        'categories_cache_ttl_hours' => (int) env('POLICE_UK_CATEGORIES_CACHE_TTL_HOURS', 24),
        'provider_request_limit' => (int) env('POLICE_UK_PROVIDER_REQUEST_LIMIT', 120),
        'provider_limit_window_seconds' => (int) env('POLICE_UK_PROVIDER_LIMIT_WINDOW_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Agency flood-monitoring provider
    |--------------------------------------------------------------------------
    |
    | This England-only public source needs short caches. Warning responses are
    | deliberately not served stale: callers must receive a controlled 503
    | rather than an old safety-relevant warning state when the source fails.
    |
    */

    'environment_agency' => [
        'base_url' => env('ENVIRONMENT_AGENCY_BASE_URL', 'https://environment.data.gov.uk/flood-monitoring'),
        'user_agent' => env('ENVIRONMENT_AGENCY_USER_AGENT', 'UKApi.co.uk/1.0'),
        'connect_timeout_seconds' => (int) env('ENVIRONMENT_AGENCY_CONNECT_TIMEOUT', 2),
        'timeout_seconds' => (int) env('ENVIRONMENT_AGENCY_TIMEOUT', 5),
        'cache_ttl_minutes' => (int) env('ENVIRONMENT_AGENCY_CACHE_TTL_MINUTES', 5),
        'nearby_distance_kilometres' => (int) env('ENVIRONMENT_AGENCY_NEARBY_DISTANCE_KILOMETRES', 10),
        'max_results' => (int) env('ENVIRONMENT_AGENCY_MAX_RESULTS', 100),
        'max_readings' => (int) env('ENVIRONMENT_AGENCY_MAX_READINGS', 100),
        'provider_request_limit' => (int) env('ENVIRONMENT_AGENCY_PROVIDER_REQUEST_LIMIT', 120),
        'provider_limit_window_seconds' => (int) env('ENVIRONMENT_AGENCY_PROVIDER_LIMIT_WINDOW_SECONDS', 60),
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
        'refresh_enabled' => (bool) env('SIC_REFERENCE_REFRESH_ENABLED', false),
        'cache_ttl_hours' => (int) env('SIC_REFERENCE_CACHE_TTL_HOURS', 24),
    ],

];

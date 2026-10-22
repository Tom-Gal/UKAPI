<?php

return [
    'enabled' => (bool) env('HEALTH_MONITORING_ENABLED', true),
    // The scheduler runs every five minutes. Old observations must not stay green.
    'stale_after_seconds' => (int) env('HEALTH_STALE_AFTER_SECONDS', 900),
    'connect_timeout_seconds' => (int) env('HEALTH_CONNECT_TIMEOUT_SECONDS', 2),
    'timeout_seconds' => (int) env('HEALTH_TIMEOUT_SECONDS', 5),
    'max_response_bytes' => 262_144,
    // Police.uk reports the month of the latest data, not its publication time.
    'crime_source_max_age_days' => (int) env('HEALTH_CRIME_SOURCE_MAX_AGE_DAYS', 120),
    'sic_retrieval_max_age_days' => (int) env('HEALTH_SIC_RETRIEVAL_MAX_AGE_DAYS', 90),
    'alerts' => [
        'enabled' => (bool) env('HEALTH_ALERTS_ENABLED', false),
        'email' => env('HEALTH_ALERT_EMAIL'),
        'failure_threshold' => (int) env('HEALTH_ALERT_FAILURE_THRESHOLD', 2),
        // Zero means one failure email per incident, without reminders.
        'repeat_after_minutes' => (int) env('HEALTH_ALERT_REPEAT_AFTER_MINUTES', 360),
        'recovery' => (bool) env('HEALTH_ALERT_RECOVERY', true),
    ],
];

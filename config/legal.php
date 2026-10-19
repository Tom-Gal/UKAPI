<?php

return [
    'entity_name' => env('LEGAL_ENTITY_NAME', 'UKAPI'),
    'company_number' => env('LEGAL_COMPANY_NUMBER', '[COMPANY NUMBER]'),
    'registered_office' => env('LEGAL_REGISTERED_OFFICE', '[REGISTERED OFFICE ADDRESS]'),
    'support_email' => env('LEGAL_SUPPORT_EMAIL', 'support@ukapi.co.uk'),
    'privacy_email' => env('LEGAL_PRIVACY_EMAIL', 'privacy@ukapi.co.uk'),
    'security_email' => env('LEGAL_SECURITY_EMAIL', 'security@ukapi.co.uk'),
    'legal_email' => env('LEGAL_LEGAL_EMAIL', 'legal@ukapi.co.uk'),
    'ico_number' => env('LEGAL_ICO_NUMBER', '[ICO REGISTRATION NUMBER]'),
    'last_updated' => env('LEGAL_LAST_UPDATED', '18 October 2026'),
];

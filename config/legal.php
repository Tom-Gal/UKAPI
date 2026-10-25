<?php

return [
    'entity_name' => env('LEGAL_ENTITY_NAME'),
    'company_number' => env('LEGAL_COMPANY_NUMBER'),
    'registered_office' => env('LEGAL_REGISTERED_OFFICE'),
    'contact_email' => env('LEGAL_CONTACT_EMAIL', 'contact@ukapi.co.uk'),
    'privacy_email' => env('LEGAL_PRIVACY_EMAIL', 'privacy@ukapi.co.uk'),
    'security_email' => env('LEGAL_SECURITY_EMAIL', 'security@ukapi.co.uk'),
    'legal_email' => env('LEGAL_LEGAL_EMAIL', 'legal@ukapi.co.uk'),
    'ico_number' => env('LEGAL_ICO_NUMBER'),
    'last_updated' => env('LEGAL_LAST_UPDATED', '18 October 2026'),
];

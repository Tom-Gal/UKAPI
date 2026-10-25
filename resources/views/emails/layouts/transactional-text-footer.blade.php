
—
{{ config('legal.entity_name') ?: 'UKAPI' }}@if(config('legal.company_number')), company number {{ config('legal.company_number') }}@endif.
@if(config('legal.registered_office'))Registered office: {{ config('legal.registered_office') }}.
@endif
Contact: {{ config('legal.contact_email') }}
This is a service message, not marketing.
Terms: {{ url('/terms') }}
Paid Plan Terms: {{ url('/paid-plan-terms') }}
Privacy Notice: {{ url('/privacy') }}
Acceptable Use Policy: {{ url('/acceptable-use') }}

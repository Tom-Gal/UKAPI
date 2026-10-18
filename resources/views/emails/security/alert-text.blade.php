Hi {{ $name }},

{{ $heading }}

{{ $alertMessage }}

@if ($activityDetails)
{{ $activityDetails }}

@endif

Review your security settings: {{ $securityUrl }}
@include('emails.layouts.transactional-text-footer')

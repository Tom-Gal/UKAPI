Hi {{ $name }},

Rotate long-lived keys regularly to limit the impact of an accidental exposure.

@foreach ($keys as $key)
- {{ $key['name'] }} ({{ $key['environment'] }}, {{ $key['ageDays'] }} days old)
@endforeach

Create a replacement key, update your integration, confirm it works, then revoke the old key. We will never email API-key secrets.

Manage API keys: {{ $apiKeysUrl }}
@include('emails.layouts.transactional-text-footer')

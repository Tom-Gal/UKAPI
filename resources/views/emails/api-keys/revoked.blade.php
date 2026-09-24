@extends('emails.layouts.transactional', ['title' => 'API key revoked', 'preheader' => 'An API key has been revoked for your UKAPI.io account.'])

@section('content')
    <p style="margin:0 0 10px;color:#b45309;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">API key activity</p>
    <h1 style="margin:0 0 14px;color:#0f172a;font-size:28px;line-height:34px;letter-spacing:-1px;">An API key was revoked</h1>
    <p style="margin:0 0 22px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, the <strong style="color:#0f172a;">{{ $keyName }}</strong> key for your <strong style="color:#0f172a;">{{ $environment }}</strong> environment can no longer make requests.</p>
    <p style="margin:0 0 24px;padding:14px 16px;border-left:3px solid #d97706;background:#fffbeb;color:#78350f;font-size:14px;line-height:22px;">If you did not make this change, review your account security and active API keys now.</p>
    <p style="margin:0;"><a href="{{ $apiKeysUrl }}" style="display:inline-block;background:#10182d;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Review API keys&nbsp; →</a></p>
@endsection

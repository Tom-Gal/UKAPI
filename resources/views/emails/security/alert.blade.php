@extends('emails.layouts.transactional', ['title' => $heading, 'preheader' => 'A security-related change was made to your UKAPI.io account.'])

@section('content')
    <p style="margin:0 0 10px;color:#b45309;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">Security notice</p>
    <h1 style="margin:0 0 14px;color:#0f172a;font-size:28px;line-height:34px;letter-spacing:-1px;">{{ $heading }}</h1>
    <p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, {{ $alertMessage }}</p>
    <p style="margin:0;"><a href="{{ $securityUrl }}" style="display:inline-block;background:#10182d;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Review security settings&nbsp; →</a></p>
@endsection

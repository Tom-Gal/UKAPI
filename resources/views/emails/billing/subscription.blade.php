@extends('emails.layouts.transactional', ['title' => $heading, 'preheader' => $heading.' for your UKApi.co.uk account.'])

@section('content')
    <p style="margin:0 0 10px;color:#2563c7;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">Billing update</p>
    <h1 style="margin:0 0 14px;color:#13231a;font-size:28px;line-height:34px;letter-spacing:-.7px;">{{ $heading }}</h1>
    <p style="margin:0 0 24px;color:#4d5b51;font-size:16px;line-height:25px;">Hi {{ $name }}, {{ $body }}</p>
    <p style="margin:0;"><a href="{{ $billingUrl }}" style="display:inline-block;background:#13231a;border:1px solid #13231a;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Manage billing&nbsp; →</a></p>
@endsection

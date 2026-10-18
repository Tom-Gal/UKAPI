@extends('emails.layouts.transactional', ['title' => $heading, 'preheader' => 'A security-related change was made to your UKApi.co.uk account.'])

@section('content')
    <p style="margin:0 0 10px;color:#b45309;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">Security notice</p>
    <h1 style="margin:0 0 14px;color:#13231a;font-size:28px;line-height:34px;letter-spacing:-.7px;">{{ $heading }}</h1>
    <p style="margin:0 0 24px;color:#4d5b51;font-size:16px;line-height:25px;">Hi {{ $name }}, {{ $alertMessage }}</p>
    @if ($activityDetails)
        <p style="margin:0 0 24px;padding:12px 14px;border:1px solid #d8ded9;background:#f8faf9;color:#4d5b51;font-family:Consolas,'Courier New',monospace;font-size:12px;line-height:20px;white-space:pre-line;">{{ $activityDetails }}</p>
    @endif
    <p style="margin:0;"><a href="{{ $securityUrl }}" style="display:inline-block;background:#13231a;border:1px solid #13231a;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Review security settings&nbsp; →</a></p>
@endsection

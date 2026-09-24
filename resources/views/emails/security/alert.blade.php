@extends('emails.layouts.transactional', ['title' => $heading])

@section('content')
    <h1 style="margin:0 0 16px;font-size:25px;line-height:32px;letter-spacing:-.5px;">{{ $heading }}</h1>
    <p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, {{ $alertMessage }}</p>
    <p style="margin:0;"><a href="{{ $securityUrl }}" style="display:inline-block;background:#172033;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Review security settings</a></p>
@endsection

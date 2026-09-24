@extends('emails.layouts.transactional', ['title' => 'Reset your password'])

@section('content')
    <h1 style="margin:0 0 16px;font-size:25px;line-height:32px;letter-spacing:-.5px;">Reset your password</h1>
    <p style="margin:0 0 24px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, we received a request to reset your UKAPI.io password.</p>
    <p style="margin:0 0 26px;"><a href="{{ $resetUrl }}" style="display:inline-block;background:#2563eb;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Reset password</a></p>
    <p style="margin:0;color:#64748b;font-size:14px;line-height:22px;">This link expires in {{ $expiresInMinutes }} minutes. If you did not request a password reset, no action is needed.</p>
@endsection

@extends('emails.layouts.transactional', ['title' => 'Verify your email', 'preheader' => 'Confirm your email address to activate your UKApi.co.uk account.'])

@section('content')
    <p style="margin:0 0 10px;color:#2563c7;font-size:11px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">One last step</p>
    <h1 style="margin:0 0 14px;color:#13231a;font-size:28px;line-height:34px;letter-spacing:-.7px;">Verify your email address</h1>
    <p style="margin:0 0 24px;color:#4d5b51;font-size:16px;line-height:25px;">Hi {{ $name }}, thanks for creating a UKApi.co.uk account. Confirm your address and you’ll be ready to build with UK data.</p>
    <p style="margin:0 0 26px;"><a href="{{ $verificationUrl }}" style="display:inline-block;background:#2563c7;border:1px solid #1d4f9f;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Verify email address&nbsp; →</a></p>
    <p style="margin:0;padding:14px 16px;border-left:3px solid #2563c7;background:#edf4ff;color:#4d5b51;font-size:14px;line-height:22px;">This secure link expires in {{ $expiresInMinutes }} minutes. If you did not create this account, you can safely ignore this email.</p>
@endsection

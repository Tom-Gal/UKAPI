@extends('emails.layouts.transactional', ['title' => 'API key created', 'preheader' => 'A new API key was created for your UKAPI.io account.'])

@section('content')
    <p style="margin:0 0 10px;color:#1248e8;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">API key activity</p>
    <h1 style="margin:0 0 14px;color:#0f172a;font-size:28px;line-height:34px;letter-spacing:-1px;">A new API key is ready</h1>
    <p style="margin:0 0 22px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, the <strong style="color:#0f172a;">{{ $keyName }}</strong> key was created for your <strong style="color:#0f172a;">{{ $environment }}</strong> environment.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">
        <tr><td style="padding:14px 16px;color:#475569;font-size:13px;line-height:20px;"><strong style="display:block;margin-bottom:3px;color:#0f172a;font-family:Consolas,'Courier New',monospace;font-size:12px;letter-spacing:.04em;text-transform:uppercase;">Security reminder</strong>The key secret is shown only when created and is never sent by email.</td></tr>
    </table>
    <p style="margin:0;"><a href="{{ $apiKeysUrl }}" style="display:inline-block;background:#10182d;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Manage API keys&nbsp; →</a></p>
@endsection

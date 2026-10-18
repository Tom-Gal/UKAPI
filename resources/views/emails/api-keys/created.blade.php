@extends('emails.layouts.transactional', ['title' => 'API key created', 'preheader' => 'A new API key was created for your UKAPI.io account.'])

@section('content')
    <p style="margin:0 0 10px;color:#2563c7;font-size:11px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">API key activity</p>
    <h1 style="margin:0 0 14px;color:#13231a;font-size:28px;line-height:34px;letter-spacing:-.7px;">A new API key is ready</h1>
    <p style="margin:0 0 22px;color:#4d5b51;font-size:16px;line-height:25px;">Hi {{ $name }}, the <strong style="color:#13231a;">{{ $keyName }}</strong> key was created for your <strong style="color:#13231a;">{{ $environment }}</strong> environment.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #cbd3cd;background:#f5f7f4;">
        <tr><td style="padding:14px 16px;color:#4d5b51;font-size:13px;line-height:20px;"><strong style="display:block;margin-bottom:3px;color:#13231a;font-family:Consolas,'Courier New',monospace;font-size:11px;letter-spacing:.08em;text-transform:uppercase;">Security reminder</strong>The key secret is shown only when created and is never sent by email.</td></tr>
    </table>
    <p style="margin:0;"><a href="{{ $apiKeysUrl }}" style="display:inline-block;background:#2563c7;border:1px solid #1d4f9f;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Manage API keys&nbsp; →</a></p>
@endsection

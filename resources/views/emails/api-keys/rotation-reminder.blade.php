@extends('emails.layouts.transactional', ['title' => 'Rotate your API keys', 'preheader' => 'One or more active API keys are due for rotation.'])

@section('content')
    <p style="margin:0 0 10px;color:#b45309;font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">Security reminder</p>
    <h1 style="margin:0 0 14px;color:#13231a;font-size:28px;line-height:34px;letter-spacing:-.7px;">Rotate your API keys</h1>
    <p style="margin:0 0 18px;color:#4d5b51;font-size:16px;line-height:25px;">Hi {{ $name }}, rotate long-lived keys regularly to limit the impact of an accidental exposure.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;border:1px solid #d8ded9;border-collapse:collapse;">
        @foreach ($keys as $key)
            <tr>
                <td style="padding:11px 14px;border-bottom:1px solid #d8ded9;color:#13231a;font-size:14px;font-weight:700;line-height:20px;">{{ $key['name'] }}</td>
                <td align="right" style="padding:11px 14px;border-bottom:1px solid #d8ded9;color:#65736a;font-family:Consolas,'Courier New',monospace;font-size:12px;line-height:20px;">{{ $key['environment'] }} · {{ $key['ageDays'] }} days</td>
            </tr>
        @endforeach
    </table>
    <p style="margin:0 0 24px;padding:14px 16px;border-left:3px solid #d97706;background:#fffbeb;color:#78350f;font-size:14px;line-height:22px;">Create a replacement key, update your integration, confirm it works, then revoke the old key. We will never email API-key secrets.</p>
    <p style="margin:0;"><a href="{{ $apiKeysUrl }}" style="display:inline-block;background:#13231a;border:1px solid #13231a;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">Manage API keys&nbsp; →</a></p>
@endsection

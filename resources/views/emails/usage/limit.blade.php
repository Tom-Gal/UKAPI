@extends('emails.layouts.transactional', ['title' => $atLimit ? 'Monthly API limit reached' : 'Monthly API limit reminder', 'preheader' => $atLimit ? 'Your plan’s monthly request allowance has been used.' : 'Your monthly UKAPI.io request allowance is nearly used.'])

@section('content')
    <p style="margin:0 0 10px;color:{{ $atLimit ? '#b45309' : '#1248e8' }};font-size:12px;font-weight:700;letter-spacing:.14em;line-height:16px;text-transform:uppercase;">Usage update</p>
    <h1 style="margin:0 0 16px;color:#0f172a;font-size:28px;line-height:34px;letter-spacing:-1px;">{{ $atLimit ? 'Your monthly API limit has been reached' : 'You are nearing your monthly API limit' }}</h1>
    <p style="margin:0 0 20px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, you have used <strong style="color:#172033;">{{ number_format($used) }}</strong> of <strong style="color:#172033;">{{ number_format($limit) }}</strong> requests this month ({{ $percentage }}%).</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr><td style="background:#e2e8f0;border-radius:999px;height:8px;overflow:hidden;"><div style="background:{{ $atLimit ? '#d97706' : '#1248e8' }};border-radius:999px;height:8px;width:{{ min(100, $percentage) }}%;"></div></td></tr>
    </table>
    <p style="margin:0 0 24px;color:#64748b;font-size:14px;line-height:22px;">{{ $atLimit ? 'Requests will be available again' : 'Your monthly allowance resets' }} on {{ $resetsAt }}.</p>
    <p style="margin:0;"><a href="{{ $dashboardUrl }}" style="display:inline-block;background:#10182d;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">View dashboard&nbsp; →</a></p>
@endsection

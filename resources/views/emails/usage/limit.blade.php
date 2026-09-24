@extends('emails.layouts.transactional', ['title' => $atLimit ? 'Monthly API limit reached' : 'Monthly API limit reminder'])

@section('content')
    <h1 style="margin:0 0 16px;font-size:25px;line-height:32px;letter-spacing:-.5px;">{{ $atLimit ? 'Your monthly API limit has been reached' : 'You are nearing your monthly API limit' }}</h1>
    <p style="margin:0 0 20px;color:#475569;font-size:16px;line-height:25px;">Hi {{ $name }}, you have used <strong style="color:#172033;">{{ number_format($used) }}</strong> of <strong style="color:#172033;">{{ number_format($limit) }}</strong> requests this month ({{ $percentage }}%).</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px;">
        <tr><td style="background:#e8eef8;border-radius:999px;height:8px;overflow:hidden;"><div style="background:#2563eb;border-radius:999px;height:8px;width:{{ min(100, $percentage) }}%;"></div></td></tr>
    </table>
    <p style="margin:0 0 24px;color:#64748b;font-size:14px;line-height:22px;">{{ $atLimit ? 'Requests will be available again' : 'Your monthly allowance resets' }} on {{ $resetsAt }}.</p>
    <p style="margin:0;"><a href="{{ $dashboardUrl }}" style="display:inline-block;background:#172033;border-radius:8px;color:#ffffff;font-size:15px;font-weight:700;line-height:20px;padding:12px 18px;text-decoration:none;">View dashboard</a></p>
@endsection

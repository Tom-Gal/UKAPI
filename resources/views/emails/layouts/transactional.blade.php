<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
    <!--[if !mso]><!-->
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell { padding: 20px 12px !important; }
            .email-card { padding: 28px 24px !important; }
            .email-footer { padding-left: 12px !important; padding-right: 12px !important; }
        }
    </style>
    <!--<![endif]-->
</head>
<body style="margin:0;background:#f7f9fc;color:#0f172a;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;visibility:hidden;mso-hide:all;">{{ $preheader ?? 'An update from your UKAPI.io account.' }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="background:#f7f9fc;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                <tr>
                    <td style="padding:0 8px 16px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="width:28px;vertical-align:middle;">
                                    <span style="display:inline-block;box-sizing:border-box;width:24px;height:24px;border:3px solid #0f172a;border-top:0;border-radius:0 0 10px 10px;vertical-align:middle;"></span>
                                </td>
                                <td style="padding-left:8px;color:#0f172a;font-size:17px;font-weight:700;letter-spacing:-.8px;line-height:24px;vertical-align:middle;">UKAPI<span style="color:#1248e8;">.io</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="overflow:hidden;border:1px solid #e2e8f0;border-radius:16px;background:#ffffff;box-shadow:0 12px 32px rgba(15,23,42,.07);">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="background:#10182d;padding:14px 36px;">
                                    <span style="font-family:Consolas,'Courier New',monospace;font-size:12px;font-weight:700;letter-spacing:.04em;color:#bfdbfe;">UKAPI.IO / ACCOUNT</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="email-card" style="padding:34px 36px;">
                                    @yield('content')
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-footer" style="padding:20px 8px 0;color:#64748b;font-size:12px;line-height:19px;">
                        <a href="{{ url('/docs') }}" style="color:#475569;text-decoration:underline;">Documentation</a><span style="color:#cbd5e1;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><a href="{{ url('/status') }}" style="color:#475569;text-decoration:underline;">Service status</a><span style="color:#cbd5e1;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><a href="{{ url('/dashboard') }}" style="color:#475569;text-decoration:underline;">Dashboard</a><br>
                        This is an automated account message from UKAPI.io. Please do not reply to this email.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

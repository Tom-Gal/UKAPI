<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f5f7fb;color:#172033;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f5f7fb;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                <tr>
                    <td style="padding:0 8px 18px;font-size:22px;font-weight:800;letter-spacing:-1px;color:#172033;">
                        UK<span style="color:#2563eb;">API</span><span style="color:#64748b;">.io</span>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff;border:1px solid #e5eaf2;border-radius:16px;padding:38px 36px;box-shadow:0 1px 2px rgba(15,23,42,.04);">
                        @yield('content')
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 8px 0;color:#64748b;font-size:12px;line-height:18px;">
                        This is an automated account message from UKAPI.io. Please do not reply to this email.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

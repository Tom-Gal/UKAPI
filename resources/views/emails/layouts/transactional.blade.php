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
            .email-shell { padding: 18px 10px !important; }
            .email-card { padding: 28px 22px !important; }
            .email-footer { padding-left: 4px !important; padding-right: 4px !important; }
        }
    </style>
    <!--<![endif]-->
</head>
<body style="margin:0;background:#f3f6f4;color:#13231a;font-family:Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;visibility:hidden;mso-hide:all;">{{ $preheader ?? 'An update from your UKApi.co.uk account.' }}</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="background:#f3f6f4;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;">
                <tr>
                    <td style="padding:0 0 18px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="width:34px;height:34px;border:1px solid #13231a;text-align:center;color:#13231a;font-size:18px;font-weight:700;line-height:34px;vertical-align:middle;">U<span style="color:#2563c7;">·</span></td>
                                <td style="padding-left:10px;color:#13231a;font-size:16px;font-weight:700;letter-spacing:-.4px;line-height:18px;vertical-align:middle;">UKApi.co.uk<br><span style="color:#65736a;font-family:Consolas,'Courier New',monospace;font-size:9px;font-weight:400;letter-spacing:.12em;text-transform:uppercase;">Data field service</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="border:1px solid #bfc9c1;border-top:4px solid #2563c7;background:#ffffff;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td style="border-bottom:1px solid #d8ded9;padding:12px 36px;color:#5b6960;font-family:Consolas,'Courier New',monospace;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">UKApi.co.uk / Account notice</td>
                            </tr>
                            <tr>
                                <td class="email-card" style="padding:36px;">
                                    @yield('content')
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="email-footer" style="padding:20px 0 0;color:#65736a;font-size:12px;line-height:19px;">
                        <a href="{{ url('/docs') }}" style="color:#315f9d;text-decoration:underline;">Documentation</a><span style="color:#b7c0ba;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><a href="{{ url('/status') }}" style="color:#315f9d;text-decoration:underline;">Service status</a><span style="color:#b7c0ba;">&nbsp;&nbsp;·&nbsp;&nbsp;</span><a href="{{ url('/dashboard') }}" style="color:#315f9d;text-decoration:underline;">Dashboard</a><br>
                        Automated account message from UKApi.co.uk. Please do not reply.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

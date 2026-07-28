<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Tarrlok')</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#a20513;padding:22px 28px;color:#ffffff;">
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:700;letter-spacing:0.02em;">Tarrlok</div>
                            <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;opacity:0.9;margin-top:4px;text-transform:uppercase;letter-spacing:0.08em;">
                                Blockchain-Based Blood Bank Network
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:#a20513;">
                            @yield('eyebrow', 'Official Notice')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.65;color:#1f2937;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 28px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#374151;border-top:1px solid #e5e7eb;">
                            <p style="margin:20px 0 4px;">Yours faithfully,</p>
                            <p style="margin:0 0 2px;font-weight:700;color:#111827;">Tarrlok Platform Administration</p>
                            <p style="margin:0;font-size:13px;color:#6b7280;">Hospital registration desk</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f9fafb;padding:16px 28px;border-top:1px solid #e5e7eb;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#6b7280;">
                            This is an official message from the Tarrlok network. Please do not reply to this email unless instructed.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

@php
    $brand = config('app.name', 'Camela Group');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }}</title>
</head>
<body style="margin:0;background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:20px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background:linear-gradient(135deg,#0f172a,#334155);color:#fff;">
                            <div style="font-size:14px;letter-spacing:.12em;text-transform:uppercase;opacity:.8;">{{ $brand }}</div>
                            <div style="font-size:28px;font-weight:700;line-height:1.2;margin-top:10px;">Membership Application Received</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 14px;line-height:1.7;font-size:15px;">Thank you for applying, {{ $fullName }}.</p>
                            <p style="margin:0 0 14px;line-height:1.7;font-size:15px;">You submitted an application for {{ $applicationTypeLabel }}.</p>
                            <p style="margin:0 0 14px;line-height:1.7;font-size:15px;">We have successfully received your membership application.</p>
                            <p style="margin:0;line-height:1.7;font-size:15px;">Our team will review it shortly.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 32px 28px;color:#6b7280;font-size:12px;text-align:center;">
                            &copy; {{ date('Y') }} {{ $brand }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

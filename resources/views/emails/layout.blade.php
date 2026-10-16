<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:24px;background:#05070a;color:#e8ffe8;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#0c1118;border:1px solid #1f3d2a;border-radius:12px;">
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 8px;color:#c6ff3d;letter-spacing:.18em;font-size:11px;text-transform:uppercase;">Nexus</p>
                {{ $slot }}
            </td>
        </tr>
    </table>
</body>
</html>

<!doctype html>
<html>
<body style="margin:0;background:#F7F7F9;font-family:Inter,Arial,sans-serif;color:#1F2333;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px;"><tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border:1px solid #E5E7EB;">
        <tr><td style="background:#1B2A4A;padding:18px 28px;color:#fff;font-size:18px;font-weight:800;">{{ config('app.name') }}</td></tr>
        <tr><td style="border-top:3px solid #E8622C;padding:28px;font-size:15px;line-height:1.6;">
            @yield('body')
        </td></tr>
    </table>
    <p style="font-size:12px;color:#6B7280;">&copy; {{ now()->year }} {{ config('app.name') }}</p>
</td></tr></table>
</body>
</html>

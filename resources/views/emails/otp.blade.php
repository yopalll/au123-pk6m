<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<title>Kode Verifikasi VIYGO</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#111316;font-family:'Manrope',Arial,Helvetica,sans-serif;color:#e2e2e6;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#111316;padding:32px 16px;">
<tr><td align="center">

    {{-- ── Card ── --}}
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#1e2023;border:1px solid rgba(255,255,255,0.06);border-radius:14px;overflow:hidden;">

        {{-- Header --}}
        <tr>
            <td align="center" style="background:#1a1c1f;padding:34px 24px;border-bottom:1px solid rgba(255,255,255,0.06);">
                <div style="font-family:'Playfair Display',Georgia,serif;font-size:34px;font-weight:700;color:#ffb68b;letter-spacing:8px;">VIYGO</div>
                <div style="font-size:11px;letter-spacing:3px;color:#d8c2b7;text-transform:uppercase;margin-top:8px;">Beauty, Skincare &amp; Lifestyle Platform</div>
            </td>
        </tr>

        {{-- Body --}}
        <tr>
            <td align="center" style="padding:44px 40px;">
                <div style="font-family:'Playfair Display',Georgia,serif;font-size:26px;color:#e2e2e6;margin-bottom:18px;">Kode Verifikasi Kamu</div>
                <div style="font-size:16px;line-height:1.6;color:#d8c2b7;margin-bottom:34px;">
                    Hai <span style="color:#e2e2e6;font-weight:600;">{{ $user->full_name }}</span>, gunakan kode berikut untuk memverifikasi akun VIYGO kamu.
                </div>

                {{-- OTP box --}}
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 34px;">
                    <tr>
                        <td align="center" style="background:#111316;border:2px dashed rgba(255,182,139,0.45);border-radius:14px;padding:22px 40px;">
                            <span style="font-family:'Playfair Display',Georgia,serif;font-size:40px;font-weight:700;color:#ffb68b;letter-spacing:14px;padding-left:14px;">{{ $code }}</span>
                        </td>
                    </tr>
                </table>

                <div style="font-size:14px;line-height:1.7;color:#d8c2b7;margin-bottom:28px;">
                    Kode ini berlaku selama <span style="color:#ffb68b;font-weight:600;">10 menit</span>.<br>
                    Jangan bagikan kode ini kepada siapapun, termasuk tim VIYGO.
                </div>

                <div style="height:1px;background:rgba(255,255,255,0.10);margin:8px 0 24px;"></div>

                <div style="font-size:13px;line-height:1.7;color:#85858d;">
                    Jika kamu tidak meminta kode ini, abaikan email ini atau hubungi
                    <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}" style="color:#ffb68b;text-decoration:underline;">support@viygo.id</a>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Footer (luar card) ── --}}
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;margin-top:28px;">
        <tr>
            <td align="center" style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#a08d83;padding-bottom:12px;">
                <a href="{{ config('app.url') }}" style="color:#a08d83;text-decoration:none;">viygo.id</a>
                &nbsp;&middot;&nbsp;
                <a href="{{ config('app.url') }}/privacy" style="color:#a08d83;text-decoration:none;">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}" style="color:#a08d83;text-decoration:none;">Support Center</a>
            </td>
        </tr>
        <tr>
            <td align="center" style="font-size:12px;color:#5f5f66;">
                &copy; {{ date('Y') }} VIYGO &mdash; Crafted for tranquility.
            </td>
        </tr>
    </table>

</td></tr>
</table>
</body>
</html>

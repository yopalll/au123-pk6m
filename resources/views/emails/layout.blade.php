<!DOCTYPE html>
<html lang="id" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $subject ?? config('app.name') }}</title>
<style>
  body { margin: 0; padding: 0; background: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
  .wrapper { width: 100%; background: #f4f5f7; padding: 32px 16px; }
  .card { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
  .header { background: #1B2D6B; padding: 28px 36px; }
  .header-logo { color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: 3px; text-decoration: none; }
  .header-tagline { color: rgba(255,255,255,.65); font-size: 11px; margin-top: 2px; letter-spacing: .5px; }
  .body { padding: 32px 36px; color: #1a1a2e; }
  .greeting { font-size: 20px; font-weight: 700; margin: 0 0 8px; color: #1B2D6B; }
  .text { font-size: 14px; line-height: 1.7; color: #555; margin: 0 0 16px; }
  .btn { display: inline-block; background: #1B2D6B; color: #ffffff !important; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; margin: 8px 0 16px; }
  .divider { border: none; border-top: 1px solid #eee; margin: 24px 0; }
  .info-box { background: #f8f9ff; border-left: 3px solid #1B2D6B; border-radius: 6px; padding: 14px 18px; margin: 16px 0; }
  .info-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; color: #555; }
  .info-label { color: #888; }
  .info-value { font-weight: 600; color: #1a1a2e; text-align: right; }
  .badge { display: inline-block; background: #e8eeff; color: #1B2D6B; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; letter-spacing: .3px; }
  .footer { background: #f8f9ff; padding: 20px 36px; text-align: center; }
  .footer-text { font-size: 11px; color: #aaa; line-height: 1.7; }
  .footer-links a { color: #1B2D6B; text-decoration: none; font-size: 11px; margin: 0 6px; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">
    <div class="header">
      <div class="header-logo">VIYGO</div>
      <div class="header-tagline">Beauty, Skincare &amp; Lifestyle Platform</div>
    </div>
    <div class="body">
      {{ $slot }}
    </div>
    <div class="footer">
      <div class="footer-links">
        <a href="{{ config('app.url') }}">viygo.id</a> &middot;
        <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}">Hubungi Kami</a>
      </div>
      <div class="footer-text" style="margin-top:8px;">
        &copy; {{ date('Y') }} VIYGO &mdash; Jl. Sudirman, Jakarta Pusat<br>
        Email ini dikirim otomatis. Mohon tidak membalas email ini.
      </div>
    </div>
  </div>
</div>
</body>
</html>

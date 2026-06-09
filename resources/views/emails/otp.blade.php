<x-emails.layout subject="Kode Verifikasi VIYGO">

<p class="greeting">Kode Verifikasi Kamu</p>
<p class="text">Hai <strong>{{ $user->full_name }}</strong>, gunakan kode berikut untuk memverifikasi akun VIYGO kamu.</p>

<div style="text-align:center;margin:28px 0;">
  <div style="display:inline-block;background:#f0f4ff;border:2px dashed #1B2D6B;border-radius:12px;padding:20px 40px;">
    <div style="font-size:36px;font-weight:800;letter-spacing:10px;color:#1B2D6B;font-family:monospace;">{{ $code }}</div>
  </div>
</div>

<p class="text" style="text-align:center;">
  Kode ini berlaku selama <strong>10 menit</strong>.<br>
  Jangan bagikan kode ini kepada siapapun, termasuk tim VIYGO.
</p>

<hr class="divider">

<p class="text" style="font-size:12px;color:#aaa;text-align:center;margin:0;">
  Jika kamu tidak meminta kode ini, abaikan email ini atau hubungi
  <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}" style="color:#1B2D6B;">support@viygo.id</a>
</p>

</x-emails.layout>

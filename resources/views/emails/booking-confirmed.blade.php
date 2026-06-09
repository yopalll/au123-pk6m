<x-emails.layout :subject="'Booking Dikonfirmasi — ' . $order->kode_order">

<p class="greeting">Booking kamu dikonfirmasi! 🎉</p>
<p class="text">Hai <strong>{{ $order->user->full_name }}</strong>, pembayaran booking kamu telah berhasil dan sesi kecantikan kamu sudah terkonfirmasi.</p>

<div class="info-box">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Kode Booking</td>
      <td style="text-align:right;font-size:13px;font-weight:700;color:#1B2D6B;padding:3px 0;">{{ $order->kode_order }}</td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Salon</td>
      <td style="text-align:right;font-size:13px;font-weight:600;color:#1a1a2e;padding:3px 0;">{{ $order->salon->nama_salon }}</td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Tanggal</td>
      <td style="text-align:right;font-size:13px;font-weight:600;color:#1a1a2e;padding:3px 0;">{{ \Carbon\Carbon::parse($order->date_order)->translatedFormat('l, d F Y') }}</td>
    </tr>
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Alamat Salon</td>
      <td style="text-align:right;font-size:12px;color:#555;padding:3px 0;">{{ $order->salon->alamat }}</td>
    </tr>
  </table>
</div>

<hr class="divider">

<p style="font-size:12px;color:#888;margin:0 0 8px;">Layanan yang dipesan:</p>
<table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
  @foreach ($order->details as $detail)
  <tr>
    <td style="padding:5px 0;color:#444;">{{ $detail->service->nama ?? '—' }}</td>
    <td style="text-align:right;color:#1a1a2e;font-weight:600;">Rp {{ number_format($detail->harga_at_order, 0, ',', '.') }}</td>
  </tr>
  @endforeach
  @if ($order->total_diskon > 0)
  <tr>
    <td style="padding:3px 0;color:#888;">Diskon</td>
    <td style="text-align:right;color:#16a34a;font-weight:600;">- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</td>
  </tr>
  @endif
  <tr>
    <td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:8px 0;"></td>
  </tr>
  <tr>
    <td style="font-weight:700;color:#1a1a2e;font-size:14px;">Total Dibayar</td>
    <td style="text-align:right;font-weight:800;color:#1B2D6B;font-size:15px;">Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</td>
  </tr>
</table>

<hr class="divider">

<p style="text-align:center;margin:0 0 16px;">
  <a href="{{ route('booking.show', $order->kode_order) }}" class="btn">Lihat Detail Booking</a>
</p>

<p class="text" style="font-size:12px;color:#aaa;text-align:center;margin:0;">
  Pertanyaan? Hubungi kami di <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}" style="color:#1B2D6B;">{{ config('viygo.support_email', 'support@viygo.id') }}</a>
</p>

</x-emails.layout>

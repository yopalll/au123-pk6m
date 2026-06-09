<x-emails.layout :subject="'Pesanan Dikonfirmasi — ' . $order->kode_order">

<p class="greeting">Pesanan kamu sedang diproses!</p>
<p class="text">Hai <strong>{{ $order->user->full_name }}</strong>, pembayaran kamu berhasil. Tim VIYGO sedang mempersiapkan pesanan kamu.</p>

<div class="info-box">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Kode Pesanan</td>
      <td style="text-align:right;font-size:13px;font-weight:700;color:#1B2D6B;padding:3px 0;">{{ $order->kode_order }}</td>
    </tr>
    @if ($order->address)
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Dikirim ke</td>
      <td style="text-align:right;font-size:12px;color:#555;padding:3px 0;">
        {{ $order->address->label ? '[' . $order->address->label . '] ' : '' }}{{ $order->address->detail_alamat }}, {{ $order->address->kota }}
      </td>
    </tr>
    @endif
    @if ($order->kurir)
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Kurir</td>
      <td style="text-align:right;font-size:13px;font-weight:600;color:#1a1a2e;padding:3px 0;">{{ strtoupper($order->kurir) }} — {{ $order->layanan_kirim }}</td>
    </tr>
    @endif
    @if ($order->estimasi_tiba)
    <tr>
      <td style="font-size:12px;color:#888;padding:3px 0;">Estimasi Tiba</td>
      <td style="text-align:right;font-size:13px;color:#555;padding:3px 0;">{{ $order->estimasi_tiba }}</td>
    </tr>
    @endif
  </table>
</div>

<hr class="divider">

<p style="font-size:12px;color:#888;margin:0 0 8px;">Produk yang dipesan:</p>
<table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border-collapse:collapse;">
  @foreach ($order->items as $item)
  <tr>
    <td style="padding:5px 0;color:#444;">{{ $item->nama_produk }} <span style="color:#aaa;">×{{ $item->qty }}</span></td>
    <td style="text-align:right;color:#1a1a2e;font-weight:600;">Rp {{ number_format($item->harga_satuan * $item->qty, 0, ',', '.') }}</td>
  </tr>
  @endforeach
  <tr>
    <td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:8px 0;"></td>
  </tr>
  <tr>
    <td style="font-size:12px;color:#888;padding:2px 0;">Subtotal</td>
    <td style="text-align:right;font-size:12px;color:#555;padding:2px 0;">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
  </tr>
  @if ($order->biaya_kirim > 0)
  <tr>
    <td style="font-size:12px;color:#888;padding:2px 0;">Ongkos Kirim</td>
    <td style="text-align:right;font-size:12px;color:#555;padding:2px 0;">Rp {{ number_format($order->biaya_kirim, 0, ',', '.') }}</td>
  </tr>
  @endif
  @if (($order->total_diskon + $order->potongan_poin) > 0)
  <tr>
    <td style="font-size:12px;color:#888;padding:2px 0;">Diskon &amp; Poin</td>
    <td style="text-align:right;font-size:12px;color:#16a34a;padding:2px 0;">- Rp {{ number_format($order->total_diskon + $order->potongan_poin, 0, ',', '.') }}</td>
  </tr>
  @endif
  <tr>
    <td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:4px 0;"></td>
  </tr>
  <tr>
    <td style="font-weight:700;color:#1a1a2e;font-size:14px;">Total Dibayar</td>
    <td style="text-align:right;font-weight:800;color:#1B2D6B;font-size:15px;">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
  </tr>
</table>

<hr class="divider">

<p style="text-align:center;margin:0 0 16px;">
  <a href="{{ route('shop.order.show', $order->kode_order) }}" class="btn">Lihat Detail Pesanan</a>
</p>

<p class="text" style="font-size:12px;color:#aaa;text-align:center;margin:0;">
  Pertanyaan? Hubungi kami di <a href="mailto:{{ config('viygo.support_email', 'support@viygo.id') }}" style="color:#1B2D6B;">{{ config('viygo.support_email', 'support@viygo.id') }}</a>
</p>

</x-emails.layout>

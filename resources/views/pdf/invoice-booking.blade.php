<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    .header { background: #1B2D6B; color: #fff; padding: 22px 28px; }
    .header table { width: 100%; }
    .logo { font-size: 24px; font-weight: bold; letter-spacing: 3px; }
    .inv-label { text-align: right; font-size: 16px; }
    .inv-code { font-size: 12px; opacity: .85; margin-top: 3px; }
    .cols { width: 100%; }
    .cols td { width: 50%; vertical-align: top; padding: 18px 28px; }
    .section-title { font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 6px; letter-spacing: .5px; }
    .body-pad { padding: 6px 28px 0; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th { text-align: left; font-size: 11px; color: #888; border-bottom: 1px solid #e5e7eb; padding: 6px 4px; }
    table.items td { padding: 8px 4px; border-bottom: 1px solid #f3f4f6; }
    .ta-r { text-align: right; }
    table.sum { width: 45%; margin-left: 55%; margin-top: 8px; }
    table.sum td { padding: 4px 4px; }
    .sum-total td { border-top: 2px solid #333; font-weight: bold; padding-top: 8px; font-size: 13px; }
    .pay { padding: 14px 28px; }
    .footer { margin-top: 16px; padding: 14px 28px; background: #f9fafb; text-align: center; font-size: 11px; color: #999; }
    .status-pill { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 10px; background:#e0e7ff; color:#1B2D6B; }
</style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td class="logo">VIYGO</td>
            <td class="inv-label">
                INVOICE
                <div class="inv-code">{{ $order->kode_order }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="cols">
    <tr>
        <td>
            <div class="section-title">Pelanggan</div>
            <div>{{ $order->user->full_name }}</div>
            <div style="color:#666">{{ $order->user->email }}</div>
            <div style="color:#666">{{ $order->user->phone_number ?? '—' }}</div>
        </td>
        <td>
            <div class="section-title">Salon</div>
            <div>{{ $order->salon->nama_salon }}</div>
            <div style="color:#666">{{ $order->salon->alamat }}</div>
            <div style="color:#666">{{ $order->salon->phone_number }}</div>
        </td>
    </tr>
</table>

<div class="body-pad">
    <div class="section-title">Rincian Layanan</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:6%">No</th>
                <th>Layanan</th>
                <th>Staff</th>
                <th>Durasi</th>
                <th class="ta-r">Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->details as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->service->nama ?? '—' }}</td>
                    <td>{{ $detail->staff->name ?? '—' }}</td>
                    <td>{{ $detail->service->durasi ?? '—' }} min</td>
                    <td class="ta-r">{{ \App\Support\Money::rupiah($detail->harga_at_order) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sum">
        <tr>
            <td>Subtotal</td>
            <td class="ta-r">{{ \App\Support\Money::rupiah($order->details->sum('subtotal')) }}</td>
        </tr>
        @if ($order->total_diskon > 0)
            <tr>
                <td>Diskon</td>
                <td class="ta-r">- {{ \App\Support\Money::rupiah($order->total_diskon) }}</td>
            </tr>
        @endif
        <tr class="sum-total">
            <td>TOTAL</td>
            <td class="ta-r">{{ \App\Support\Money::rupiah($order->total_pembayaran) }}</td>
        </tr>
    </table>
</div>

<div class="pay">
    <div class="section-title">Informasi Pembayaran</div>
    <div>Metode: {{ ucfirst($order->pembayaran->metode_pembayaran ?? '—') }}
        &nbsp; <span class="status-pill">{{ ucfirst($order->pembayaran->status_pembayaran ?? '—') }}</span></div>
    @if ($order->pembayaran?->tanggal_bayar)
        <div style="color:#666">Tanggal Bayar: {{ \Carbon\Carbon::parse($order->pembayaran->tanggal_bayar)->format('d M Y, H:i') }}</div>
    @endif
    @if ($order->pembayaran?->id_transaksi)
        <div style="color:#666">ID Transaksi: {{ $order->pembayaran->id_transaksi }}</div>
    @endif
</div>

<div class="footer">
    Terima kasih telah menggunakan VIYGO<br>
    Dicetak pada {{ now()->format('d M Y, H:i') }} · VIYGO — Beauty, Skincare & Lifestyle Platform
</div>

</body>
</html>

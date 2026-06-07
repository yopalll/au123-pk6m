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
    table.sum { width: 50%; margin-left: 50%; margin-top: 8px; }
    table.sum td { padding: 3px 4px; }
    .sum-total td { border-top: 2px solid #333; font-weight: bold; padding-top: 8px; font-size: 13px; }
    .footer { margin-top: 16px; padding: 14px 28px; background: #f9fafb; text-align: center; font-size: 11px; color: #999; }
</style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td class="logo">VIYGO</td>
            <td class="inv-label">INVOICE<div class="inv-code">{{ $order->kode_order }}</div></td>
        </tr>
    </table>
</div>

<table class="cols">
    <tr>
        <td>
            <div class="section-title">Pelanggan</div>
            <div>{{ $order->user->full_name }}</div>
            <div style="color:#666">{{ $order->user->email }}</div>
        </td>
        <td>
            <div class="section-title">Dikirim ke</div>
            @if ($order->address)
                <div>{{ $order->address->nama_penerima }} ({{ $order->address->phone }})</div>
                <div style="color:#666">{{ $order->address->alamat_lengkap }}, {{ $order->address->kota }} {{ $order->address->kode_pos }}</div>
            @endif
        </td>
    </tr>
</table>

<div class="body-pad">
    <div class="section-title">Produk</div>
    <table class="items">
        <thead>
            <tr><th style="width:6%">No</th><th>Produk</th><th>Qty</th><th class="ta-r">Harga</th><th class="ta-r">Subtotal</th></tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama_produk }}</td>
                    <td>{{ $item->qty }}</td>
                    <td class="ta-r">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="ta-r">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sum">
        <tr><td>Subtotal</td><td class="ta-r">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Ongkir ({{ strtoupper($order->kurir) }} {{ $order->layanan_kirim }})</td><td class="ta-r">{{ $order->biaya_kirim > 0 ? 'Rp '.number_format($order->biaya_kirim,0,',','.') : 'GRATIS' }}</td></tr>
        @if ($order->total_diskon > 0)<tr><td>Diskon</td><td class="ta-r">- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</td></tr>@endif
        @if ($order->potongan_poin > 0)<tr><td>Poin ({{ $order->poin_digunakan }})</td><td class="ta-r">- Rp {{ number_format($order->potongan_poin, 0, ',', '.') }}</td></tr>@endif
        <tr class="sum-total"><td>TOTAL</td><td class="ta-r">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td></tr>
    </table>
</div>

<div class="footer">
    Terima kasih telah berbelanja di VIYGO<br>
    Dicetak {{ now()->format('d M Y, H:i') }} · Status: {{ ucfirst($order->status) }}
</div>

</body>
</html>

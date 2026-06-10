<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
@php
    $statusColors = [
        'pending'   => '#ffb68b', 'paid' => '#abcdcd', 'completed' => '#abcdcd',
        'settlement'=> '#abcdcd', 'success' => '#abcdcd', 'failed' => '#ffb4ab',
        'cancel'    => '#ffb4ab', 'expire' => '#ffb4ab',
    ];
    $payStatus = $order->pembayaran->status_pembayaran ?? '—';
    $sc = $statusColors[strtolower($payStatus)] ?? '#ffb68b';
@endphp
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { background: #111316; font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #e2e2e6; }
    .serif { font-family: 'DejaVu Serif', serif; }
    .page { padding: 34px; }
    .card { background: #1a1c1f; border: 1px solid #2f3236; border-radius: 12px; }
    .accent-line { height: 2px; background: #ffb68b; opacity: .55; }

    .header { padding: 34px 40px; border-bottom: 1px solid #3a2f29; }
    .header table { width: 100%; }
    .header td { vertical-align: top; }
    .brand { font-size: 38px; font-weight: 700; color: #ffb68b; letter-spacing: -1px; }
    .klabel { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #a08d83; }
    .inv-code { font-size: 13px; color: #ffb68b; font-weight: bold; margin-top: 3px; }
    .right { text-align: right; }
    .date-val { font-size: 13px; color: #e2e2e6; margin-top: 4px; }
    .pill { display: inline-block; margin-top: 12px; border: 1px solid {{ $sc }}; color: {{ $sc }};
            background: #1e2023; font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
            padding: 5px 16px; border-radius: 999px; }

    .parties { padding: 30px 40px; background: #161819; }
    .parties table { width: 100%; }
    .parties td { width: 50%; vertical-align: top; }
    .plabel { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #ffb68b;
              border-bottom: 1px solid #3a2f29; padding-bottom: 6px; margin-bottom: 10px; }
    .pname { font-family: 'DejaVu Serif', serif; font-size: 18px; color: #e2e2e6; margin-bottom: 4px; }
    .psoft { font-size: 12px; color: #a08d83; line-height: 1.6; }

    .items-wrap { padding: 26px 40px; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items th { text-align: left; font-size: 10px; font-weight: normal; letter-spacing: 1.5px;
                     text-transform: uppercase; color: #a08d83; border-bottom: 1px solid #3a3d42; padding: 0 6px 14px; }
    table.items td { padding: 16px 6px; border-bottom: 1px solid #232629; vertical-align: top; }
    .it-name { font-size: 13px; color: #e2e2e6; }
    .ta-c { text-align: center; } .ta-r { text-align: right; }
    .amt-primary { color: #ffb68b; font-weight: bold; }
    .muted { color: #a08d83; }

    .summary { padding: 26px 40px; background: #1d1f22; }
    table.sum { width: 58%; margin-left: 42%; }
    table.sum td { padding: 7px 6px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #a08d83; }
    table.sum td.amt { text-align: right; text-transform: none; letter-spacing: 0; color: #e2e2e6; }
    .sum-div td { border-top: 1px solid #3a2f29; padding: 0; height: 1px; line-height: 0; }
    .total-row td { padding-top: 16px; vertical-align: bottom; }
    .total-lbl { font-family: 'DejaVu Serif', serif; font-size: 22px; color: #ffb68b; text-transform: none; letter-spacing: 0; }
    .gt-cap { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #a08d83; }
    .gt-amt { font-family: 'DejaVu Serif', serif; font-size: 26px; color: #e2e2e6; font-weight: bold; }

    .pay { padding: 22px 40px; border-top: 1px solid #232629; }
    .pay .plabel { display: block; }
    .pay-row { font-size: 12px; color: #a08d83; margin-top: 5px; }
    .pay-row b { color: #e2e2e6; font-weight: normal; }

    .footer { padding: 26px 40px; text-align: center; }
    .quote { font-size: 12px; font-style: italic; color: #a08d83; line-height: 1.6; }
    .legal { margin-top: 22px; padding-top: 16px; border-top: 1px solid #232629;
             font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: #5f5f66; }
</style>
</head>
<body>
<div class="page">
    <div class="card">
        <div class="accent-line"></div>

        <div class="header">
            <table>
                <tr>
                    <td>
                        <div class="brand serif">VIYG&Ouml;</div>
                        <div style="margin-top:10px;">
                            <div class="klabel">Invoice</div>
                            <div class="inv-code">{{ $order->kode_order }}</div>
                        </div>
                    </td>
                    <td class="right">
                        <div class="klabel">Date Issued</div>
                        <div class="date-val">{{ $order->created_at?->format('d M Y') ?? now()->format('d M Y') }}</div>
                        <div class="pill">{{ strtoupper($payStatus) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="parties">
            <table>
                <tr>
                    <td style="padding-right:24px;">
                        <div class="plabel">Pelanggan</div>
                        <div class="pname">{{ $order->user->full_name }}</div>
                        <div class="psoft">{{ $order->user->email }}</div>
                        <div class="psoft">{{ $order->user->phone_number ?? '—' }}</div>
                    </td>
                    <td style="padding-left:24px;">
                        <div class="plabel">Salon</div>
                        <div class="pname">{{ $order->salon->nama_salon }}</div>
                        <div class="psoft">{{ $order->salon->alamat }}</div>
                        <div class="psoft">{{ $order->salon->phone_number }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th style="width:6%;">No</th>
                        <th>Layanan</th>
                        <th>Staff</th>
                        <th class="ta-c" style="width:14%;">Durasi</th>
                        <th class="ta-r" style="width:24%;">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->details as $i => $detail)
                        <tr>
                            <td class="muted">{{ $i + 1 }}</td>
                            <td><div class="it-name">{{ $detail->service->nama ?? '—' }}</div></td>
                            <td class="muted">{{ $detail->staff->name ?? '—' }}</td>
                            <td class="ta-c muted">{{ $detail->service->durasi ?? '—' }} min</td>
                            <td class="ta-r amt-primary">{{ \App\Support\Money::rupiah($detail->harga_at_order) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="summary">
            <table class="sum">
                <tr>
                    <td>Subtotal</td>
                    <td class="amt">{{ \App\Support\Money::rupiah($order->details->sum('subtotal')) }}</td>
                </tr>
                @if ($order->total_diskon > 0)
                    <tr><td>Diskon</td><td class="amt">- {{ \App\Support\Money::rupiah($order->total_diskon) }}</td></tr>
                @endif
                <tr class="sum-div"><td colspan="2"></td></tr>
                <tr class="total-row">
                    <td class="total-lbl">Total</td>
                    <td class="ta-r">
                        <div class="gt-cap">Grand Total (IDR)</div>
                        <div class="gt-amt">{{ \App\Support\Money::rupiah($order->total_pembayaran) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="pay">
            <div class="plabel">Informasi Pembayaran</div>
            <div class="pay-row">Metode: <b>{{ ucfirst($order->pembayaran->metode_pembayaran ?? '—') }}</b></div>
            @if ($order->pembayaran?->tanggal_bayar)
                <div class="pay-row">Tanggal Bayar: <b>{{ \Carbon\Carbon::parse($order->pembayaran->tanggal_bayar)->format('d M Y, H:i') }}</b></div>
            @endif
            @if ($order->pembayaran?->id_transaksi)
                <div class="pay-row">ID Transaksi: <b>{{ $order->pembayaran->id_transaksi }}</b></div>
            @endif
        </div>

        <div class="footer">
            <div class="quote">&ldquo;Terima kasih telah mempercayakan perawatanmu pada VIYGO.&rdquo;</div>
            <div class="legal">&copy; {{ date('Y') }} VIYG&Ouml;. All rights reserved. &bull; Private &amp; Confidential</div>
        </div>
    </div>
</div>
</body>
</html>

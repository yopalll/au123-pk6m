<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
@php
    $statusColors = [
        'pending'    => '#ffb68b',
        'paid'       => '#abcdcd',
        'processing' => '#a5cbea',
        'shipped'    => '#a5cbea',
        'delivered'  => '#abcdcd',
        'completed'  => '#abcdcd',
        'cancelled'  => '#ffb4ab',
        'refunded'   => '#ffb4ab',
    ];
    $sc = $statusColors[$order->status] ?? '#ffb68b';
@endphp
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { background: #111316; font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #e2e2e6; }
    .serif { font-family: 'DejaVu Serif', serif; }
    .page { padding: 34px; }
    .card { background: #1a1c1f; border: 1px solid #2f3236; border-radius: 12px; }
    .accent-line { height: 2px; background: #ffb68b; opacity: .55; }

    /* Header */
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

    /* Parties */
    .parties { padding: 30px 40px; background: #161819; }
    .parties table { width: 100%; }
    .parties td { width: 50%; vertical-align: top; }
    .plabel { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #ffb68b;
              border-bottom: 1px solid #3a2f29; padding-bottom: 6px; margin-bottom: 10px; }
    .pname { font-family: 'DejaVu Serif', serif; font-size: 18px; color: #e2e2e6; margin-bottom: 4px; }
    .psoft { font-size: 12px; color: #a08d83; line-height: 1.6; }

    /* Items */
    .items-wrap { padding: 26px 40px; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items th { text-align: left; font-size: 10px; font-weight: normal; letter-spacing: 1.5px;
                     text-transform: uppercase; color: #a08d83; border-bottom: 1px solid #3a3d42; padding: 0 6px 14px; }
    table.items td { padding: 18px 6px; border-bottom: 1px solid #232629; vertical-align: top; }
    .thumb { width: 46px; height: 46px; background: #111316; border: 1px solid #3a2f29; border-radius: 8px; }
    .it-name { font-size: 14px; color: #e2e2e6; }
    .it-sub { font-size: 10px; color: #a08d83; margin-top: 3px; letter-spacing: .5px; }
    .ta-c { text-align: center; }
    .ta-r { text-align: right; }
    .amt-primary { color: #ffb68b; font-weight: bold; }
    .muted { color: #a08d83; }

    /* Summary */
    .summary { padding: 26px 40px; background: #1d1f22; }
    table.sum { width: 58%; margin-left: 42%; }
    table.sum td { padding: 7px 6px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #a08d83; }
    table.sum td.amt { text-align: right; text-transform: none; letter-spacing: 0; color: #e2e2e6; }
    .gratis { color: #abcdcd; font-weight: bold; letter-spacing: 2px; }
    .sum-div td { border-top: 1px solid #3a2f29; padding: 0; height: 1px; line-height: 0; }
    .total-row td { padding-top: 16px; vertical-align: bottom; }
    .total-lbl { font-family: 'DejaVu Serif', serif; font-size: 22px; color: #ffb68b; text-transform: none; letter-spacing: 0; }
    .gt-cap { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #a08d83; }
    .gt-amt { font-family: 'DejaVu Serif', serif; font-size: 26px; color: #e2e2e6; font-weight: bold; }

    /* Footer */
    .footer { padding: 30px 40px; text-align: center; }
    .quote { font-size: 12px; font-style: italic; color: #a08d83; line-height: 1.6; }
    .legal { margin-top: 24px; padding-top: 18px; border-top: 1px solid #232629;
             font-size: 9px; letter-spacing: 3px; text-transform: uppercase; color: #5f5f66; }
</style>
</head>
<body>
<div class="page">
    <div class="card">
        <div class="accent-line"></div>

        {{-- Header --}}
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
                        <div class="pill">{{ strtoupper($order->status) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Billed / Shipping --}}
        <div class="parties">
            <table>
                <tr>
                    <td style="padding-right:24px;">
                        <div class="plabel">Billed To</div>
                        <div class="pname">{{ $order->user->full_name }}</div>
                        <div class="psoft">{{ $order->user->email }}</div>
                    </td>
                    <td style="padding-left:24px;">
                        <div class="plabel">Shipping To</div>
                        @if ($order->address)
                            <div class="pname">{{ $order->address->nama_penerima }}</div>
                            <div class="psoft">{{ $order->address->phone }}</div>
                            <div class="psoft">{{ $order->address->alamat_lengkap }}, {{ $order->address->kota }} {{ $order->address->kode_pos }}</div>
                        @else
                            <div class="psoft">&mdash;</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items --}}
        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="ta-c" style="width:10%;">Qty</th>
                        <th class="ta-r" style="width:24%;">Unit Price</th>
                        <th class="ta-r" style="width:24%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <table cellpadding="0" cellspacing="0"><tr>
                                    <td style="vertical-align:top;padding-right:14px;"><div class="thumb"></div></td>
                                    <td style="vertical-align:top;">
                                        <div class="it-name">{{ $item->nama_produk }}</div>
                                        <div class="it-sub">VIYGO Skincare</div>
                                    </td>
                                </tr></table>
                            </td>
                            <td class="ta-c">{{ $item->qty }}</td>
                            <td class="ta-r muted">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                            <td class="ta-r amt-primary">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="summary">
            <table class="sum">
                <tr>
                    <td>Subtotal</td>
                    <td class="amt">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td class="amt">
                        @if ($order->biaya_kirim > 0)
                            Rp {{ number_format($order->biaya_kirim, 0, ',', '.') }}
                        @else
                            <span class="gratis">GRATIS</span>
                        @endif
                    </td>
                </tr>
                @if ($order->total_diskon > 0)
                    <tr><td>Diskon</td><td class="amt">- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</td></tr>
                @endif
                @if ($order->potongan_poin > 0)
                    <tr><td>Poin ({{ $order->poin_digunakan }})</td><td class="amt">- Rp {{ number_format($order->potongan_poin, 0, ',', '.') }}</td></tr>
                @endif
                <tr class="sum-div"><td colspan="2"></td></tr>
                <tr class="total-row">
                    <td class="total-lbl">Total</td>
                    <td class="ta-r">
                        <div class="gt-cap">Grand Total (IDR)</div>
                        <div class="gt-amt">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="quote">&ldquo;Terima kasih telah memilih VIYGO. Semoga perjalanan self-care kamu seistimewa kehadiranmu.&rdquo;</div>
            <div class="legal">&copy; {{ date('Y') }} VIYG&Ouml;. All rights reserved. &bull; Private &amp; Confidential</div>
        </div>
    </div>
</div>
</body>
</html>

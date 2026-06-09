<x-layouts.public title="Rincian Booking">
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-10">

    {{-- Breadcrumb --}}
    <a href="{{ route('akun.bookings') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B] transition-colors">
        ← Kembali ke Booking Saya
    </a>

    {{-- Header: kode + status --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 mb-8 gap-3">
        <div>
            <p class="text-sm text-gray-500">Kode Pesanan</p>
            <h1 class="text-2xl font-semibold text-gray-900" style="font-family:'DM Serif Display',serif">
                {{ $order->kode_order }}
            </h1>
        </div>
        @php
            $badge = match ($order->status) {
                'pending'             => 'bg-amber-100 text-amber-700',
                'confirmed'           => 'bg-blue-100 text-blue-700',
                'success', 'completed'=> 'bg-emerald-100 text-emerald-700',
                'canceled', 'cancelled'=> 'bg-red-100 text-red-700',
                default               => 'bg-gray-100 text-gray-600',
            };
        @endphp
        <span class="self-start px-4 py-1.5 rounded-full text-sm font-semibold {{ $badge }}">
            {{ ucfirst($order->status) }}
        </span>
    </div>

    {{-- Info Salon --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-5">
        <h2 class="font-semibold text-lg mb-4">Informasi Salon</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <a href="{{ route('salon.show', $order->salon->slug ?? $order->salon->id_salon) }}"
                   class="font-medium text-gray-900 hover:text-[#1B2D6B] transition-colors">
                    {{ $order->salon->nama_salon }}
                </a>
                <p class="text-sm text-gray-500 mt-1">{{ $order->salon->alamat }}</p>
                <p class="text-sm text-gray-500">{{ $order->salon->phone_number }}</p>
            </div>
            @if ($order->salon->latitude && $order->salon->longitude)
                <div id="booking-map" class="w-full h-40 rounded-xl bg-gray-100"
                     data-lat="{{ $order->salon->latitude }}"
                     data-lng="{{ $order->salon->longitude }}"
                     data-label="{{ $order->salon->nama_salon }}"></div>
            @endif
        </div>
    </div>

    {{-- Rincian Service --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-5">
        <h2 class="font-semibold text-lg mb-4">Rincian Layanan</h2>

        {{-- Desktop: tabel --}}
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-500">
                        <th class="text-left py-2 font-medium">Layanan</th>
                        <th class="text-left py-2 font-medium">Staff</th>
                        <th class="text-left py-2 font-medium">Durasi</th>
                        <th class="text-right py-2 font-medium">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->details as $detail)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-3">{{ $detail->service->nama ?? '—' }}</td>
                            <td class="py-3 text-gray-600">{{ $detail->staff->name ?? '—' }}</td>
                            <td class="py-3 text-gray-600">{{ $detail->service->durasi ?? '—' }} min</td>
                            <td class="py-3 text-right">{{ \App\Support\Money::rupiah($detail->harga_at_order) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile: kartu --}}
        <div class="sm:hidden space-y-3">
            @foreach ($order->details as $detail)
                <div class="border border-gray-100 rounded-xl p-4">
                    <p class="font-medium">{{ $detail->service->nama ?? '—' }}</p>
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>{{ $detail->staff->name ?? '—' }} · {{ $detail->service->durasi ?? '—' }} min</span>
                        <span>{{ \App\Support\Money::rupiah($detail->harga_at_order) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Ringkasan Pembayaran --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-5">
        <h2 class="font-semibold text-lg mb-4">Ringkasan Pembayaran</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>{{ \App\Support\Money::rupiah($order->details->sum('subtotal')) }}</span>
            </div>
            @if ($order->total_diskon > 0)
                <div class="flex justify-between text-emerald-600">
                    <span>Diskon</span>
                    <span>- {{ \App\Support\Money::rupiah($order->total_diskon) }}</span>
                </div>
            @endif
            <div class="flex justify-between font-semibold text-base border-t border-gray-100 pt-2 mt-2">
                <span>Total Pembayaran</span>
                <span>{{ \App\Support\Money::rupiah($order->total_pembayaran) }}</span>
            </div>
        </div>
        @if ($order->pembayaran)
            <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-500 space-y-0.5">
                <p>Metode: {{ ucfirst($order->pembayaran->metode_pembayaran ?? '—') }}</p>
                <p>Status: <span class="font-medium text-gray-700">{{ ucfirst($order->pembayaran->status_pembayaran ?? '—') }}</span></p>
                @if ($order->pembayaran->id_transaksi)
                    <p>ID Transaksi: {{ $order->pembayaran->id_transaksi }}</p>
                @endif
            </div>
        @endif
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
        <h2 class="font-semibold text-lg mb-4">Status Pesanan</h2>
        @php
            $paid = in_array($order->status, ['confirmed', 'success', 'completed']);
            $done = in_array($order->status, ['success', 'completed']);
            $timeline = [
                ['Booking Dibuat',      true,  $order->created_at],
                ['Pembayaran Berhasil', $paid, $order->pembayaran?->tanggal_bayar],
                ['Treatment Selesai',   $done, $done ? $order->updated_at : null],
            ];
        @endphp
        @foreach ($timeline as [$label, $ok, $time])
            <div class="flex items-start gap-4 mb-4 last:mb-0">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0
                            {{ $ok ? 'bg-emerald-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                    {{ $ok ? '✓' : '○' }}
                </div>
                <div>
                    <p class="text-sm font-medium {{ $ok ? 'text-gray-900' : 'text-gray-400' }}">{{ $label }}</p>
                    @if ($ok && $time)
                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($time)->translatedFormat('d M Y, H:i') }}</p>
                    @elseif (! $ok)
                        <p class="text-xs text-gray-400">Menunggu</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Aksi --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('akun.booking.invoice', $order->kode_order) }}"
           class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
            📄 Download Invoice PDF
        </a>

        @if (in_array($order->status, ['pending', 'confirmed']))
            <form method="POST" action="{{ route('booking.batal', $order->kode_order) }}"
                  onsubmit="return confirm('Yakin batalkan booking ini?')">
                @csrf
                <button type="submit"
                        class="w-full sm:w-auto px-5 py-3 border border-red-200 text-red-600 text-sm font-semibold rounded-full hover:bg-red-50 transition-colors">
                    Batalkan Booking
                </button>
            </form>
        @endif

        @if (in_array($order->status, ['success', 'completed']) && ! $order->review)
            <a href="{{ route('akun.review.create', $order->kode_order) }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-3 border border-gray-200 text-gray-700 text-sm font-semibold rounded-full hover:border-[#4BA3CC] transition-colors">
                ⭐ Tulis Review
            </a>
        @endif
    </div>
</div>

<x-slot:scripts>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('booking-map');
        if (el && window.L) {
            const lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
            const map = L.map('booking-map', { scrollWheelZoom: false }).setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
            L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.label);
        }
    });
</script>
</x-slot:scripts>
</x-layouts.public>

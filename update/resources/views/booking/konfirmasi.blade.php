<x-layouts.public title="Booking Dikonfirmasi!">
<div class="max-w-lg mx-auto px-6 py-16 text-center">

    {{-- Success Icon --}}
    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-3xl text-[#1B2D6B] mb-3">Booking Berhasil!</h1>
    <p class="text-gray-500 mb-8">Kode booking kamu: <strong class="text-[#1B2D6B] font-mono text-lg">{{ $order->kode_order }}</strong></p>

    {{-- Summary Card --}}
    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-6 text-left space-y-3 mb-8">
        @foreach([
            ['Salon',    $order->salon->nama_salon],
            ['Layanan',  $order->details->first()?->service?->nama ?? '-'],
            ['Tanggal',  \Carbon\Carbon::parse($order->date_order)->isoFormat('dddd, D MMMM Y')],
            ['Alamat',   $order->salon->alamat],
            ['Total',    'Rp '.number_format($order->total_pembayaran, 0, ',', '.')],
        ] as [$label, $value])
            <div class="flex justify-between text-sm {{ !$loop->last ? 'border-b border-[#C5E1F0] pb-3' : 'font-semibold text-base pt-1' }}">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="text-gray-900 text-right max-w-[60%]">{{ $value }}</span>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 mb-8">Konfirmasi booking telah dikirim ke email kamu. Pembayaran dilakukan langsung di salon.</p>

    <div class="flex gap-3 justify-center">
        <a href="{{ route('akun.bookings') }}"
           class="px-6 py-3 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors">
            Lihat Booking Saya
        </a>
        <a href="{{ route('home') }}"
           class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold hover:bg-[#4BA3CC] transition-colors">
            Kembali ke Beranda
        </a>
    </div>
</div>
</x-layouts.public>

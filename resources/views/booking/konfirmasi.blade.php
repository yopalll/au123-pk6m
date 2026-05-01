<x-layouts.public title="Booking Confirmed">
<div class="max-w-lg mx-auto px-6 py-16 text-center">

    <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h1 class="text-3xl text-[#1B2D6B] mb-3">Booking Confirmed!</h1>
    <p class="text-gray-500 mb-8">Your booking reference: <strong class="text-[#1B2D6B] font-mono text-lg">{{ $order->kode_order }}</strong></p>

    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-6 text-left space-y-3 mb-8">
        @foreach([
            ['Salon',    $order->salon->nama_salon],
            ['Service',  $order->details->first()?->service?->nama ?? '-'],
            ['Date',     \Carbon\Carbon::parse($order->date_order)->isoFormat('dddd, D MMMM Y')],
            ['Time',     $order->details->first()?->start_time ?? '-'],
            ['Address',  $order->salon->alamat],
            ['Total',    '£'.number_format($order->total_pembayaran, 2, '.', ',')],
        ] as [$label, $value])
            <div class="flex justify-between text-sm {{ !$loop->last ? 'border-b border-[#C5E1F0] pb-3' : 'font-semibold text-base pt-1' }}">
                <span class="text-gray-500">{{ $label }}</span>
                <span class="text-gray-900 text-right max-w-[60%]">{{ $value }}</span>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 mb-8">A confirmation email is on its way. Payment is taken directly at the salon.</p>

    <div class="flex gap-3 justify-center">
        <a href="{{ route('akun.bookings') }}"
           class="px-6 py-3 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors">
            View My Bookings
        </a>
        <a href="{{ route('home') }}"
           class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold hover:bg-[#4BA3CC] transition-colors">
            Back to Home
        </a>
    </div>
</div>
</x-layouts.public>

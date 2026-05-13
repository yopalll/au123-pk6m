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
        <div class="flex justify-between text-sm border-b border-[#C5E1F0] pb-3">
            <span class="text-gray-500">Salon</span>
            <span class="text-gray-900 text-right max-w-[60%]">{{ $order->salon->nama_salon }}</span>
        </div>

        <div class="border-b border-[#C5E1F0] pb-3">
            <div class="text-sm text-gray-500 mb-2">Services ({{ $order->details->count() }})</div>
            <ul class="space-y-1.5">
                @foreach ($order->details as $detail)
                    <li class="flex justify-between text-sm">
                        <span class="text-gray-700">
                            {{ $detail->service?->nama ?? '—' }}
                            <span class="text-xs text-gray-400">({{ $detail->start_time }}–{{ $detail->end_time }})</span>
                        </span>
                        <span class="font-medium text-gray-900">£{{ number_format($detail->subtotal, 2, '.', ',') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex justify-between text-sm border-b border-[#C5E1F0] pb-3">
            <span class="text-gray-500">Date</span>
            <span class="text-gray-900 text-right max-w-[60%]">{{ \Carbon\Carbon::parse($order->date_order)->isoFormat('dddd, D MMMM Y') }}</span>
        </div>

        <div class="flex justify-between text-sm border-b border-[#C5E1F0] pb-3">
            <span class="text-gray-500">Start time</span>
            <span class="text-gray-900 text-right max-w-[60%]">{{ $order->details->first()?->start_time ?? '-' }}</span>
        </div>

        <div class="flex justify-between text-sm border-b border-[#C5E1F0] pb-3">
            <span class="text-gray-500">Address</span>
            <span class="text-gray-900 text-right max-w-[60%]">{{ $order->salon->alamat }}</span>
        </div>

        <div class="flex justify-between font-semibold text-base pt-1">
            <span class="text-gray-500">Total</span>
            <span class="text-gray-900">£{{ number_format($order->total_pembayaran, 2, '.', ',') }}</span>
        </div>
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

<x-layouts.public title="My Bookings">
<div class="max-w-3xl mx-auto px-6 py-10">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.index') }}" class="text-[#4BA3CC] hover:underline text-sm">← Account</a>
        <h1 class="text-2xl text-[#1B2D6B]">My Bookings</h1>
    </div>

    {{-- Tabs (server-driven via ?tab=) --}}
    <div class="flex gap-1 border-b border-gray-200 mb-6">
        @foreach(['mendatang'=>'Upcoming','selesai'=>'Completed','dibatalkan'=>'Cancelled'] as $key => $label)
            <a href="{{ route('akun.bookings', ['tab' => $key]) }}"
               class="px-4 pb-3 text-sm transition-colors
                      {{ ($tab ?? 'mendatang') === $key ? 'border-b-2 border-[#1B2D6B] text-[#1B2D6B] font-semibold' : 'text-gray-500 hover:text-gray-800' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @php
        use App\Constants\OrderStatus;

        $statusLabels = [
            OrderStatus::PENDING   => 'Awaiting payment',
            OrderStatus::CONFIRMED => 'Paid',
            OrderStatus::SUCCESS   => 'Completed',
            OrderStatus::CANCELED  => 'Cancelled',
        ];
        $statusBadgeClass = [
            OrderStatus::PENDING   => 'bg-amber-100 text-amber-700',
            OrderStatus::CONFIRMED => 'bg-blue-100 text-blue-700',
            OrderStatus::SUCCESS   => 'bg-green-100 text-green-700',
            OrderStatus::CANCELED  => 'bg-red-100 text-red-600',
        ];
    @endphp

    @forelse ($orders as $order)
        <div class="border border-gray-100 rounded-2xl overflow-hidden mb-4 hover:border-[#C5E1F0] transition-colors">
            <div class="flex items-center justify-between p-5 bg-gray-50 border-b border-gray-100">
                <div>
                    <span class="text-xs font-mono font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ $order->kode_order }}</span>
                    <span class="ml-3 text-xs font-semibold px-2 py-0.5 rounded-full {{ $statusBadgeClass[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                    </span>
                </div>
                <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($order->date_order)->isoFormat('D MMM Y') }}</span>
            </div>
            <div class="p-5 flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-2xl shrink-0">✂️</div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900">{{ $order->salon->nama_salon }}</div>
                    <div class="text-sm text-gray-500">{{ $order->details->pluck('service.nama')->implode(', ') }}</div>
                    @if ($order->salon->kota?->nama)
                        <div class="text-sm text-gray-400 mt-0.5">{{ $order->salon->kota->nama }}</div>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <div class="font-bold text-[#1B2D6B]">£{{ number_format($order->total_pembayaran, 2, '.', ',') }}</div>
                    <div class="flex gap-2 mt-2 justify-end items-center">
                        @if ($order->status === 'pending')
                            <a href="{{ route('booking.payment', $order->kode_order) }}"
                               class="text-xs px-3 py-1 rounded-full bg-[#1B2D6B] text-white hover:bg-[#4BA3CC] transition-colors">
                                Pay now
                            </a>
                            <form action="{{ route('booking.batal', $order->kode_order) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="text-xs text-red-500 hover:underline"
                                        onclick="return confirm('Cancel this booking?')">
                                    Cancel
                                </button>
                            </form>
                        @endif
                        @if ($order->status === 'confirmed')
                            <span class="text-xs text-gray-400">
                                Contact <a href="{{ route('static.help') }}" class="underline">support</a> to cancel
                            </span>
                        @endif
                        @if ($order->status === 'success')
                            @if ($order->review)
                                <span class="text-xs text-gray-400 inline-flex items-center gap-1"
                                      title="You rated {{ $order->review->rating }} / 5">
                                    ★ {{ $order->review->rating }}/5 reviewed
                                </span>
                            @else
                                <a href="{{ route('akun.review.create', $order->kode_order) }}"
                                   class="text-xs px-3 py-1 rounded-full bg-[#1B2D6B] text-white hover:bg-[#4BA3CC] transition-colors">
                                    Leave a review
                                </a>
                            @endif
                            <a href="{{ route('salon.show', $order->salon->slug ?? $order->salon->id_salon) }}"
                               class="text-xs text-[#4BA3CC] hover:underline">Book again</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="py-16 text-center">
            <div class="text-5xl mb-4">📅</div>
            <p class="text-lg text-gray-500 mb-2">No bookings yet.</p>
            <p class="text-sm text-gray-400 mb-6">Find your favourite salon and make your first booking.</p>
            <a href="{{ route('cari') }}"
               class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
                Explore Salons
            </a>
        </div>
    @endforelse

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
</x-layouts.public>

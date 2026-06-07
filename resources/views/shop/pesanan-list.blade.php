<x-layouts.public title="Pesanan Produk">
@php
    $imgUrl = fn ($url) => $url ? asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url) : 'https://placehold.co/120x120/1a1c1f/ffb68b?text=VIYGO';
    $badgeCls = fn ($s) => match ($s) {
        'pending' => 'bg-amber-100 text-amber-700', 'paid','processing' => 'bg-blue-100 text-blue-700',
        'shipped' => 'bg-indigo-100 text-indigo-700', 'delivered','completed' => 'bg-emerald-100 text-emerald-700',
        'cancelled','refunded' => 'bg-red-100 text-red-700', default => 'bg-gray-100 text-gray-600',
    };
@endphp
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">Pesanan Produk</h1>

    @forelse ($orders as $order)
        <a href="{{ route('shop.order.show', $order->kode_order) }}"
           class="block bg-white border border-gray-100 rounded-2xl p-4 mb-3 hover:border-[#4BA3CC] transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-mono font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">{{ $order->kode_order }}</span>
                <span class="text-xs px-2 py-1 rounded-full {{ $badgeCls($order->status) }}">{{ ucfirst($order->status) }}</span>
            </div>
            <div class="flex gap-2 mb-3">
                @foreach ($order->items->take(4) as $item)
                    <img src="{{ $imgUrl($item->product?->primaryImage?->image_url) }}" class="w-12 h-12 rounded-lg object-cover">
                @endforeach
                @if ($order->items->count() > 4)<span class="text-xs text-gray-400 self-center">+{{ $order->items->count()-4 }}</span>@endif
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-400">{{ $order->created_at->translatedFormat('d M Y') }} · {{ $order->items->count() }} item</span>
                <span class="font-bold text-[#1B2D6B]">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
            </div>
        </a>
    @empty
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">📦</p>
            <p class="mb-4">Belum ada pesanan produk.</p>
            <a href="{{ route('shop.index') }}" class="inline-block px-5 py-2.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Mulai Belanja</a>
        </div>
    @endforelse

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
</x-layouts.public>

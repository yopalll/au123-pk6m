<x-layouts.public title="Keranjang">
@php
    $imgUrl = fn ($url) => $url ? asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url) : 'https://placehold.co/200x200/1a1c1f/ffb68b?text=VIYGO';
    $remaining = max(0, $threshold - $subtotal);
    $pct = $threshold > 0 ? min(100, round($subtotal / $threshold * 100)) : 100;
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">Keranjang</h1>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif

    @if ($items->count())
        {{-- Free ongkir progress --}}
        <div class="bg-[#E8F4FB] rounded-2xl p-4 mb-6">
            @if ($remaining > 0)
                <p class="text-sm text-[#1B2D6B]">Belanja <strong>Rp {{ number_format($remaining,0,',','.') }}</strong> lagi untuk <strong>gratis ongkir</strong>! 🎉</p>
            @else
                <p class="text-sm text-[#1B2D6B]">🎉 Selamat! Kamu dapat <strong>gratis ongkir</strong>.</p>
            @endif
            <div class="w-full h-2 bg-white rounded-full mt-2 overflow-hidden">
                <div class="h-full bg-[#1B2D6B] rounded-full transition-all" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        <div class="space-y-3">
            @foreach ($items as $item)
                <div class="flex gap-4 bg-white border border-gray-100 rounded-2xl p-3">
                    <img src="{{ $imgUrl($item->product->primaryImage?->image_url) }}" class="w-20 h-20 rounded-xl object-cover shrink-0">
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('shop.produk.show', $item->product->slug) }}" class="text-sm font-medium hover:text-[#1B2D6B] line-clamp-2">{{ $item->product->nama }}</a>
                        <p class="text-sm font-bold text-[#1B2D6B] mt-1">Rp {{ number_format($item->product->harga, 0, ',', '.') }}</p>
                        <div class="flex items-center justify-between mt-2">
                            {{-- Qty --}}
                            <form method="POST" action="{{ route('shop.cart.update') }}" class="flex items-center gap-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="id_cart" value="{{ $item->id_cart }}">
                                <button name="qty" value="{{ max(1,$item->qty-1) }}" class="w-7 h-7 rounded-full border border-gray-200 text-gray-600">−</button>
                                <span class="text-sm w-6 text-center">{{ $item->qty }}</span>
                                <button name="qty" value="{{ $item->qty+1 }}" class="w-7 h-7 rounded-full border border-gray-200 text-gray-600">+</button>
                            </form>
                            <form method="POST" action="{{ route('shop.cart.remove', $item->id_cart) }}">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold">Rp {{ number_format($item->product->harga * $item->qty, 0, ',', '.') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-5 mt-6">
            <div class="flex justify-between text-sm mb-2"><span class="text-gray-500">Subtotal</span><span class="font-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
            <p class="text-xs text-gray-400 mb-4">Ongkir & diskon dihitung di halaman checkout.</p>
            <a href="{{ route('shop.checkout') }}" class="block text-center py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Lanjut ke Checkout
            </a>
        </div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">🛒</p>
            <p class="mb-4">Keranjangmu masih kosong.</p>
            <a href="{{ route('shop.index') }}" class="inline-block px-5 py-2.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Mulai Belanja</a>
        </div>
    @endif
</div>
</x-layouts.public>

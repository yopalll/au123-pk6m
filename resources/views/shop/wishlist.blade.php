<x-layouts.public title="Wishlist">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">Wishlist</h1>
        @if ($items->count())
            <button onclick="navigator.clipboard.writeText('{{ route('shop.wishlist.share', auth()->id()) }}').then(()=>alert('Link wishlist disalin!'))"
                    class="text-sm px-4 py-2 border border-gray-200 rounded-full hover:border-[#4BA3CC] transition-colors">🔗 Bagikan</button>
        @endif
    </div>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif

    @if ($items->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($items as $item)
                @if ($item->product)
                    <div class="wishlist-item">
                        <x-product-card :product="$item->product" :wishlisted="true" />
                        <button type="button" class="add-to-cart w-full mt-2 py-2 text-xs font-semibold bg-[#1B2D6B] text-white rounded-full hover:bg-[#4BA3CC] transition-colors"
                                data-product="{{ $item->product->id_product }}" data-qty="1">
                            🛒 Pindah ke Keranjang
                        </button>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">🤍</p>
            <p class="mb-4">Belum ada produk di wishlist.</p>
            <a href="{{ route('shop.index') }}" class="inline-block px-5 py-2.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Jelajahi Produk</a>
        </div>
    @endif
</div>
</x-layouts.public>

@props(['product', 'wishlisted' => false])

@php
    $img = $product->primaryImage?->image_url
        ? asset(\Illuminate\Support\Str::startsWith($product->primaryImage->image_url, 'public/')
            ? str_replace('public/', 'storage/', $product->primaryImage->image_url)
            : $product->primaryImage->image_url)
        : 'https://placehold.co/400x500/1a1c1f/ffb68b?text=' . urlencode($product->nama);
    $harga    = $product->harga_diskon ?? $product->harga;
    $hasDisc  = $product->harga_diskon && $product->harga_diskon < $product->harga;
@endphp

<div class="group relative rounded-xl overflow-hidden bg-[#1a1c1f] border border-white/10 hover:border-[#ffb68b]/40 transition-colors">
    {{-- Badge --}}
    @if ($product->badge)
        <span class="absolute top-3 left-3 z-10 text-[10px] uppercase tracking-[0.1em] font-semibold px-2.5 py-1 rounded-full bg-[#111316]/70 backdrop-blur text-[#ffdbc8] border border-white/10">
            {{ str_replace('_', ' ', $product->badge) }}
        </span>
    @endif

    {{-- Wishlist --}}
    @auth
        <button type="button"
                class="wishlist-btn absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-[#111316]/60 backdrop-blur border border-white/10 flex items-center justify-center hover:bg-[#111316]/90 transition-colors"
                data-product="{{ $product->id_product }}" aria-label="Wishlist">
            <span class="heart text-base {{ $wishlisted ? 'text-[#ffb68b]' : 'text-white/50' }}">{{ $wishlisted ? '♥' : '♡' }}</span>
        </button>
    @endauth

    <a href="{{ route('shop.produk.show', $product->slug) }}" class="block">
        <div class="aspect-[4/5] overflow-hidden">
            <img src="{{ $img }}" alt="{{ $product->nama }}" loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        </div>

        {{-- Frosted footer --}}
        <div class="p-4 bg-[#1e2023]/60 backdrop-blur-md border-t border-white/10">
            @if ($product->collection)
                <p class="text-[10px] uppercase tracking-[0.12em] text-[#a5cbea]/80 mb-1">{{ $product->collection->nama }}</p>
            @endif
            <h3 class="font-playfair text-[15px] leading-snug text-[#e2e2e6] line-clamp-2" style="font-family:'Playfair Display',serif">{{ $product->nama }}</h3>
            <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-[#ffb68b]">Rp {{ number_format($harga, 0, ',', '.') }}</span>
                    @if ($hasDisc)<span class="text-xs text-white/35 line-through">Rp {{ number_format($product->harga, 0, ',', '.') }}</span>@endif
                </div>
                @if ($product->rating > 0)
                    <span class="text-xs text-[#ffb68b]/90">★ {{ number_format($product->rating, 1) }}</span>
                @endif
            </div>
        </div>
    </a>
</div>

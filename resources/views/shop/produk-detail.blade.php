<x-layouts.public :title="$product->nama">
@php
    $imgUrl = function ($url) {
        if (!$url) return 'https://placehold.co/600x600/1a1c1f/ffb68b?text=VIYGO';
        return asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url);
    };
    $images = $product->images->count() ? $product->images : collect([$product->primaryImage]);
    $harga  = $product->harga_diskon ?? $product->harga;
    $hasDisc = $product->harga_diskon && $product->harga_diskon < $product->harga;
@endphp

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <nav class="text-sm text-gray-400 mb-6">
        <a href="{{ route('shop.index') }}" class="hover:text-[#1B2D6B]">Shop</a> /
        <a href="{{ route('shop.kategori', $product->category->slug) }}" class="hover:text-[#1B2D6B]">{{ $product->category->nama }}</a> /
        <span class="text-gray-700">{{ $product->nama }}</span>
    </nav>

    <div class="lg:grid lg:grid-cols-2 lg:gap-10" x-data="{ main: '{{ $imgUrl($images->first()?->image_url) }}' }">
        {{-- Gallery --}}
        <div>
            <div class="aspect-square rounded-2xl overflow-hidden glass-surface">
                <img :src="main" alt="{{ $product->nama }}" class="w-full h-full object-cover">
            </div>
            @if ($images->count() > 1)
                <div class="flex gap-2 mt-3 overflow-x-auto">
                    @foreach ($images as $img)
                        <button type="button" @click="main='{{ $imgUrl($img->image_url) }}'"
                                class="w-16 h-16 rounded-lg overflow-hidden border-2 shrink-0"
                                :class="main==='{{ $imgUrl($img->image_url) }}' ? 'border-[#1B2D6B]' : 'border-transparent'">
                            <img src="{{ $imgUrl($img->image_url) }}" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="mt-6 lg:mt-0">
            @if ($product->collection)
                <a href="{{ route('shop.koleksi', $product->collection->slug) }}"
                   class="text-xs uppercase tracking-wide text-[#4BA3CC] font-semibold">{{ $product->collection->nama }}</a>
            @endif
            <h1 class="text-2xl sm:text-3xl font-semibold mt-1" style="font-family:'DM Serif Display',serif">{{ $product->nama }}</h1>
            <p class="text-sm text-gray-400 mt-1">{{ $product->brand }}@if($product->volume_ml) · {{ $product->volume_ml }} ml @endif</p>

            @if ($product->rating > 0)
                <div class="mt-2 text-sm text-amber-500">★ {{ number_format($product->rating, 1) }}
                    <span class="text-gray-400">({{ $product->total_review }} ulasan)</span>
                </div>
            @endif

            <div class="mt-4 flex items-center gap-3">
                <span class="text-2xl font-bold text-[#ffb68b]">Rp {{ number_format($harga, 0, ',', '.') }}</span>
                @if ($hasDisc)
                    <span class="text-base text-gray-400 line-through">Rp {{ number_format($product->harga, 0, ',', '.') }}</span>
                @endif
            </div>

            {{-- Badges + kategori (pivot) --}}
            <div class="flex flex-wrap gap-2 mt-3">
                @if ($product->badge)
                    <span class="text-xs px-2 py-1 rounded-full bg-[#E8F4FB] text-[#1B2D6B] font-medium">{{ str_replace('_',' ',$product->badge) }}</span>
                @endif
                @foreach ($product->categories as $cat)
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">{{ $cat->nama }}</span>
                @endforeach
            </div>

            <p class="text-sm text-gray-600 leading-relaxed mt-4">{{ $product->deskripsi }}</p>

            {{-- Stok + actions --}}
            @if ($product->stok > 0)
                <div class="mt-6 flex flex-col gap-3" x-data="{ qty: 1, max: {{ (int) min($product->stok, 99) }} }">

                    {{-- Stepper jumlah --}}
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-400">Jumlah</span>
                        <div class="flex items-center border border-gray-200 rounded-full overflow-hidden">
                            <button type="button" @click="qty = Math.max(1, qty - 1)"
                                    :disabled="qty <= 1"
                                    class="w-10 h-10 flex items-center justify-center text-lg text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">−</button>
                            <span class="w-12 text-center text-sm font-semibold tabular-nums" x-text="qty"></span>
                            <button type="button" @click="qty = Math.min(max, qty + 1)"
                                    :disabled="qty >= max"
                                    class="w-10 h-10 flex items-center justify-center text-lg text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">+</button>
                        </div>
                        <span class="text-xs text-gray-400" x-show="qty >= max">Stok maksimal</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" class="add-to-cart flex-1 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors"
                                data-product="{{ $product->id_product }}" :data-qty="qty">
                            Tambah ke Keranjang
                        </button>
                        @auth
                            <button type="button" class="wishlist-btn w-12 h-12 rounded-full border border-gray-200 flex items-center justify-center hover:border-red-300 transition-colors"
                                    data-product="{{ $product->id_product }}">
                                <span class="heart text-lg {{ $isWishlisted ? 'text-red-500' : 'text-gray-300' }}">{{ $isWishlisted ? '♥' : '♡' }}</span>
                            </button>
                        @endauth
                    </div>

                    {{-- Beli Langsung → lewati keranjang, ke checkout --}}
                    @auth
                        <form method="POST" action="{{ route('shop.buynow') }}" class="w-full">
                            @csrf
                            <input type="hidden" name="id_product" value="{{ $product->id_product }}">
                            <input type="hidden" name="qty" :value="qty">
                            <button type="submit"
                                    class="w-full py-3 border-2 border-[#ffb68b] text-[#ffb68b] text-sm font-semibold rounded-full hover:bg-[#ffb68b] hover:text-[#3a1d08] transition-colors">
                                Beli Langsung
                            </button>
                        </form>
                    @else
                        <button type="button"
                                onclick="document.getElementById('auth-modal').style.display='flex'"
                                class="w-full py-3 border-2 border-[#ffb68b] text-[#ffb68b] text-sm font-semibold rounded-full hover:bg-[#ffb68b] hover:text-[#3a1d08] transition-colors">
                            Beli Langsung
                        </button>
                    @endauth
                </div>
            @else
                <div class="mt-6">
                    <button disabled class="w-full py-3 bg-gray-200 text-gray-400 text-sm font-semibold rounded-full cursor-not-allowed">Stok Habis</button>
                </div>
            @endif

            {{-- Skin type/concern --}}
            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-400">Tipe Kulit</span><br><span class="font-medium">{{ ucfirst($product->skin_type) }}</span></div>
                @if ($product->skin_concern)
                    <div><span class="text-gray-400">Cocok untuk</span><br><span class="font-medium">{{ str_replace([',','_'],[', ',' '],$product->skin_concern) }}</span></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail sections --}}
    <div class="mt-12 grid md:grid-cols-2 gap-6">
        @if ($product->key_ingredients)
            <div class="bg-white border border-gray-100 rounded-2xl p-6">
                <h3 class="font-semibold mb-2">✨ Bahan Aktif Utama</h3>
                <p class="text-sm text-gray-600">{{ $product->key_ingredients }}</p>
            </div>
        @endif
        @if ($product->cara_pemakaian)
            <div class="bg-white border border-gray-100 rounded-2xl p-6">
                <h3 class="font-semibold mb-2">📋 Cara Pemakaian</h3>
                <p class="text-sm text-gray-600">{{ $product->cara_pemakaian }}</p>
            </div>
        @endif
        @if ($product->full_ingredients)
            <div class="bg-white border border-gray-100 rounded-2xl p-6 md:col-span-2">
                <h3 class="font-semibold mb-2">🧪 Komposisi Lengkap (INCI)</h3>
                <p class="text-xs text-gray-500 leading-relaxed">{{ $product->full_ingredients }}</p>
            </div>
        @endif
    </div>

    {{-- Reviews --}}
    <section class="mt-12">
        <h2 class="text-xl font-semibold mb-4" style="font-family:'DM Serif Display',serif">Ulasan Produk</h2>
        <div class="flex flex-wrap gap-2 mb-5">
            <a href="{{ route('shop.produk.show', $product->slug) }}" class="text-xs px-3 py-1.5 rounded-full border {{ !request('review_filter') ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-600' }}">Semua</a>
            @foreach ([5,4,3,2,1] as $star)
                <a href="{{ route('shop.produk.show', [$product->slug, 'review_filter'=>$star]) }}" class="text-xs px-3 py-1.5 rounded-full border {{ request('review_filter')==$star ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-600' }}">★ {{ $star }}</a>
            @endforeach
            <a href="{{ route('shop.produk.show', [$product->slug, 'review_filter'=>'with_photo']) }}" class="text-xs px-3 py-1.5 rounded-full border {{ request('review_filter')==='with_photo' ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-600' }}">Dengan Foto</a>
        </div>

        @forelse ($reviews as $review)
            <div class="border-b border-gray-100 py-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">{{ $review->user->full_name ?? 'Pengguna' }}</span>
                    @if ($review->is_verified_purchase)
                        <span class="text-[10px] px-1.5 py-0.5 bg-emerald-50 text-emerald-600 rounded">Verified</span>
                    @endif
                </div>
                <div class="text-amber-500 text-sm mt-0.5">{{ str_repeat('★', $review->rating) }}<span class="text-gray-200">{{ str_repeat('★', 5-$review->rating) }}</span></div>
                @if ($review->judul)<p class="font-medium text-sm mt-1">{{ $review->judul }}</p>@endif
                @if ($review->komentar)<p class="text-sm text-gray-600 mt-1">{{ $review->komentar }}</p>@endif
                @if ($review->foto_urls)
                    <div class="flex gap-2 mt-2">
                        @foreach ($review->foto_urls as $f)
                            <img src="{{ asset('storage/'.$f) }}" class="w-16 h-16 rounded-lg object-cover">
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada ulasan untuk produk ini.</p>
        @endforelse
        <div class="mt-6">{{ $reviews->links() }}</div>
    </section>

    {{-- Complete the routine --}}
    @if ($sameCollection->count())
        <section class="mt-12">
            <h2 class="text-xl font-semibold mb-4" style="font-family:'DM Serif Display',serif">Lengkapi Ritualmu</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($sameCollection as $p)<x-product-card :product="$p" />@endforeach
            </div>
        </section>
    @endif

    {{-- You may also like --}}
    @if ($related->count())
        <section class="mt-12">
            <h2 class="text-xl font-semibold mb-4" style="font-family:'DM Serif Display',serif">Kamu Mungkin Suka</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($related as $p)<x-product-card :product="$p" />@endforeach
            </div>
        </section>
    @endif
</div>
</x-layouts.public>

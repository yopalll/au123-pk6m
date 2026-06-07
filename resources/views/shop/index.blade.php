<x-layouts.public title="Shop Skincare">
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Hero --}}
    <section class="relative glass-surface rounded-3xl p-8 sm:p-14 mb-12 overflow-hidden text-center">
        <p class="text-[11px] uppercase tracking-[0.2em] text-[#ffdbc8] mb-3">VIYGO Skincare</p>
        <h1 class="text-4xl sm:text-5xl text-[#e2e2e6] mb-4" style="font-family:'Playfair Display',serif">Rawat Kulit, Jaga Bumi</h1>
        <p class="text-sm text-white/55 max-w-md mx-auto mb-7">Koleksi skincare premium berbahan alami. Temukan produk yang cocok untuk kulitmu.</p>
        <a href="{{ route('shop.skincareFinder') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-[#ffb68b] text-[#3a1d08] text-sm font-semibold rounded-full hover:bg-[#ffdbc8] transition-colors">
            <span class="material-symbols-outlined" style="font-size:18px">science</span> Coba Skincare Finder
        </a>
    </section>

    {{-- Kategori --}}
    <section class="mb-12">
        <h2 class="text-2xl text-[#e2e2e6] mb-5" style="font-family:'Playfair Display',serif">Kategori</h2>
        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
            @foreach ($categories as $cat)
                <a href="{{ route('shop.kategori', $cat->slug) }}"
                   class="shrink-0 px-5 py-2 rounded-full glass-surface text-sm text-white/70 hover:text-[#ffdbc8] hover:border-[#ffb68b]/30 transition-colors whitespace-nowrap">
                    {{ $cat->nama }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- Koleksi --}}
    @if ($collections->count())
        <section class="mb-12">
            <h2 class="text-2xl text-[#e2e2e6] mb-5" style="font-family:'Playfair Display',serif">Koleksi</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($collections as $col)
                    <a href="{{ route('shop.koleksi', $col->slug) }}"
                       class="glass-surface rounded-2xl p-6 text-center hover:border-[#ffb68b]/30 transition-colors group">
                        <span class="material-symbols-outlined text-[#a5cbea] group-hover:text-[#ffb68b] transition-colors" style="font-size:30px">spa</span>
                        <span class="block text-sm font-medium text-[#e2e2e6] mt-3" style="font-family:'Playfair Display',serif">{{ $col->nama }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Featured --}}
    @if ($featuredProducts->count())
        <section class="mb-12">
            <h2 class="text-2xl text-[#e2e2e6] mb-5" style="font-family:'Playfair Display',serif">Produk Unggulan</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach ($featuredProducts as $product)<x-product-card :product="$product" />@endforeach
            </div>
        </section>
    @endif

    {{-- Terbaru --}}
    <section>
        <h2 class="text-2xl text-[#e2e2e6] mb-5" style="font-family:'Playfair Display',serif">Produk Terbaru</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach ($latestProducts as $product)<x-product-card :product="$product" />@endforeach
        </div>
    </section>

</div>
</x-layouts.public>

<x-layouts.public :title="$category->nama">
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8" x-data="{ filterOpen: false }">

    <nav class="text-sm text-gray-400 mb-4">
        <a href="{{ route('shop.index') }}" class="hover:text-[#1B2D6B]">Shop</a> /
        <span class="text-gray-700">{{ $category->nama }}</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">{{ $category->nama }}</h1>
            <p class="text-sm text-gray-500">{{ $products->total() }} produk</p>
        </div>
        {{-- Trigger bottom-sheet (mobile) --}}
        <button @click="filterOpen = true" class="lg:hidden flex items-center gap-1.5 text-sm px-4 py-2 border border-gray-200 rounded-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 4h18M7 12h10M10 20h4"/></svg>
            Filter
        </button>
    </div>

    {{-- Backdrop bottom-sheet --}}
    <div x-show="filterOpen" x-cloak @click="filterOpen = false" class="lg:hidden fixed inset-0 bg-black/40 z-40"></div>

    <div class="lg:flex lg:gap-8">
        {{-- Sidebar (desktop) / bottom-sheet (mobile) --}}
        <aside class="shrink-0 lg:w-60
                      max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-50 max-lg:bg-white max-lg:rounded-t-3xl max-lg:shadow-2xl max-lg:p-5 max-lg:transition-transform max-lg:duration-300"
               :class="filterOpen ? 'max-lg:translate-y-0' : 'max-lg:translate-y-full lg:translate-y-0'">
            <div class="flex items-center justify-between lg:hidden mb-3">
                <span class="font-semibold">Filter & Urutkan</span>
                <button @click="filterOpen = false" class="text-gray-400 text-xl">&times;</button>
            </div>
            <form method="GET" class="bg-white lg:border lg:border-gray-100 lg:rounded-2xl lg:p-5 space-y-5 lg:sticky lg:top-24">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Urutkan</label>
                    <select name="sort" onchange="this.form.submit()"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-[#4BA3CC]">
                        @foreach (['terbaru'=>'Terbaru','terlaris'=>'Terlaris','harga_asc'=>'Harga ↑','harga_desc'=>'Harga ↓','rating'=>'Rating'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('sort')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Tipe Kulit</label>
                    <select name="skin_type"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-[#4BA3CC]">
                        <option value="">Semua</option>
                        @foreach (['oily'=>'Berminyak','dry'=>'Kering','combination'=>'Kombinasi','sensitive'=>'Sensitif','normal'=>'Normal'] as $k=>$v)
                            <option value="{{ $k }}" @selected(request('skin_type')===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Harga (Rp)</label>
                    <div class="flex gap-2">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min"
                               class="w-1/2 text-sm border border-gray-200 rounded-lg px-2 py-2 outline-none focus:border-[#4BA3CC]">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max"
                               class="w-1/2 text-sm border border-gray-200 rounded-lg px-2 py-2 outline-none focus:border-[#4BA3CC]">
                    </div>
                </div>
                <button class="w-full py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-lg hover:bg-[#4BA3CC] transition-colors">
                    Terapkan
                </button>
            </form>
        </aside>

        {{-- Grid --}}
        <div class="flex-1">
            @if ($products->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @else
                <div class="text-center py-20 text-gray-400">
                    <p class="text-4xl mb-3">🔍</p>
                    <p>Tidak ada produk yang cocok dengan filter.</p>
                </div>
            @endif
        </div>
    </div>
</div>
</x-layouts.public>

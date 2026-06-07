<x-layouts.public title="Cari Produk">
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <form action="{{ route('shop.cari') }}" method="GET" class="mb-6">
        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-full overflow-hidden max-w-xl focus-within:border-[#4BA3CC]">
            <input name="q" value="{{ $q }}" placeholder="Cari produk skincare…"
                   class="flex-1 bg-transparent px-5 py-3 text-sm outline-none">
            <button class="m-1 px-5 py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">Cari</button>
        </div>
    </form>

    @if ($q !== '')
        <p class="text-sm text-gray-500 mb-6">{{ $products->total() }} hasil untuk "<span class="font-medium text-gray-700">{{ $q }}</span>"</p>
    @endif

    @if ($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-4xl mb-3">🔍</p>
            <p>{{ $q === '' ? 'Ketik kata kunci untuk mencari produk.' : 'Tidak ada produk yang cocok.' }}</p>
        </div>
    @endif
</div>
</x-layouts.public>

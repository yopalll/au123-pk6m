<x-layouts.public :title="$collection->nama">
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <nav class="text-sm text-gray-400 mb-4">
        <a href="{{ route('shop.index') }}" class="hover:text-[#1B2D6B]">Shop</a> /
        <span class="text-gray-700">Koleksi {{ $collection->nama }}</span>
    </nav>

    <div class="rounded-3xl bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] p-8 mb-8">
        <h1 class="text-3xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $collection->nama }}</h1>
        @if ($collection->tagline)
            <p class="text-sm text-[#1B2D6B]/70 mt-2">{{ $collection->tagline }}</p>
        @endif
        <p class="text-sm text-[#1B2D6B]/60 mt-1">{{ $products->total() }} produk</p>
    </div>

    @if ($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <div class="text-center py-20 text-gray-400">Belum ada produk di koleksi ini.</div>
    @endif
</div>
</x-layouts.public>

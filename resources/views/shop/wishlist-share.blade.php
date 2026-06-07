<x-layouts.public title="Wishlist Bersama">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-1" style="font-family:'DM Serif Display',serif">Wishlist {{ $user->full_name }}</h1>
    <p class="text-sm text-gray-500 mb-6">{{ $items->count() }} produk favorit</p>

    @if ($items->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($items as $item)
                @if ($item->product)<x-product-card :product="$item->product" />@endif
            @endforeach
        </div>
    @else
        <div class="text-center py-20 text-gray-400">Wishlist ini masih kosong.</div>
    @endif
</div>
</x-layouts.public>

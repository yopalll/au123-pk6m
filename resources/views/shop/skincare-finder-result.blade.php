<x-layouts.public title="Rekomendasi Skincare">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="text-center mb-8">
        <p class="text-xs uppercase tracking-widest text-[#4BA3CC] font-semibold">Hasil Skincare Finder</p>
        <h1 class="text-3xl font-bold mt-2" style="font-family:'DM Serif Display',serif">Rekomendasi untukmu</h1>
        <p class="text-sm text-gray-500 mt-2">
            Tipe kulit: <strong>{{ ucfirst($data['skin_type']) }}</strong> ·
            Concern: <strong>{{ str_replace([',','_'],[', ',' '],$data['skin_concern']) }}</strong>
        </p>
    </div>

    @if ($products->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($products as $product)<x-product-card :product="$product" />@endforeach
        </div>
    @else
        <p class="text-center text-gray-400 py-16">Belum ada produk yang cocok. Coba kombinasi lain.</p>
    @endif

    <div class="text-center mt-10">
        <a href="{{ route('shop.skincareFinder') }}" class="text-sm text-[#4BA3CC] hover:underline">← Ulangi quiz</a>
    </div>
</div>
</x-layouts.public>

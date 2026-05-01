<x-layouts.public title="Favorit Saya">
<div class="max-w-3xl mx-auto px-6 py-10">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.index') }}" class="text-[#4BA3CC] hover:underline text-sm">← Akun</a>
        <h1 class="text-2xl text-[#1B2D6B]">Favorit Saya</h1>
    </div>

    @if (isset($favourites) && $favourites->isNotEmpty())
        <div class="divide-y divide-gray-100">
            @foreach ($favourites as $salon)
                <x-salon-card :salon="$salon" layout="list" />
            @endforeach
        </div>
    @else
        <div class="py-16 text-center">
            <div class="text-5xl mb-4">❤️</div>
            <p class="text-lg text-gray-500 mb-2">Belum ada salon favorit.</p>
            <p class="text-sm text-gray-400 mb-6">Klik ikon hati pada salon yang kamu suka untuk menyimpannya di sini.</p>
            <a href="{{ route('cari') }}"
               class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
                Temukan Salon
            </a>
        </div>
    @endif
</div>
</x-layouts.public>

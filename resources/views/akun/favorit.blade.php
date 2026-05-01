<x-layouts.public title="My Favourites">
<div class="max-w-3xl mx-auto px-6 py-10">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.index') }}" class="text-[#4BA3CC] hover:underline text-sm">← Account</a>
        <h1 class="text-2xl text-[#1B2D6B]">My Favourites</h1>
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
            <p class="text-lg text-gray-500 mb-2">No favourite salons yet.</p>
            <p class="text-sm text-gray-400 mb-6">Tap the heart icon on any salon to save it here.</p>
            <a href="{{ route('cari') }}"
               class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
                Discover Salons
            </a>
        </div>
    @endif
</div>
</x-layouts.public>

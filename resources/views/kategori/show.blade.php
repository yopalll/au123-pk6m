@php
    $heading = ($sub ?? null)?->name ?? $kategori?->name;
    $heroDesc = ($sub ?? null)?->deskripsi ?? $kategori?->deskripsi;
@endphp
<x-layouts.public :title="$heading ?? 'Category'">

@php
    $mapMarkers = $salons->getCollection()
        ->filter(fn ($s) => $s->latitude !== null && $s->longitude !== null)
        ->take(30)
        ->map(fn ($s) => [
            'lat'   => (float) $s->latitude,
            'lng'   => (float) $s->longitude,
            'title' => $s->nama_salon,
            'url'   => route('salon.show', $s->slug ?? $s->id_salon),
        ])
        ->values();
@endphp

{{-- ───── HERO BANNER ──────────────────────────────────────────────────── --}}
<div class="bg-[#1B2D6B] relative overflow-hidden">
    <div class="absolute right-0 top-0 bottom-0 w-1/2 bg-linear-to-l from-[#4BA3CC]/30 to-transparent hidden md:block"></div>
    <div class="max-w-7xl mx-auto px-6 py-10 relative z-10">
        <nav class="text-xs text-white/40 mb-4 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="hover:text-white/70">Home</a>
            <span>/</span>
            <span class="text-white/70">{{ $heading ?? 'Category' }}</span>
        </nav>
        <h1 class="text-3xl md:text-4xl text-white mb-2">{{ $heading ?? 'Treatments' }}</h1>
        <p class="text-white/60 text-sm">{{ $heroDesc ?? '' }}</p>
    </div>
</div>

{{-- ───── FILTER BAR ──────────────────────────────────────────────────── --}}
<div class="sticky z-40 bg-white border-b border-gray-100 shadow-sm" style="top: var(--navbar-h, 96px);">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-2 overflow-x-auto scrollbar-hide">

        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 6h18M7 12h10M11 18h2"/>
                </svg>
                Sort
                <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute top-full left-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-lg py-1 z-50">
                @foreach([
                    ''                  => 'Most Popular',
                    'rating-tertinggi'  => 'Top Rated',
                    'harga-terendah'    => 'Lowest Price',
                    'terbaru'           => 'Newest',
                ] as $sortKey => $opt)
                    <a href="{{ request()->fullUrlWithQuery(['sort' => $sortKey]) }}"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-[#E8F4FB] hover:text-[#1B2D6B]
                              {{ (request('sort') ?? '') === $sortKey ? 'font-semibold text-[#1B2D6B]' : '' }}">
                        {{ $opt }}
                    </a>
                @endforeach
            </div>
        </div>

        <button class="px-4 py-2 rounded-full border border-gray-200 text-gray-700 text-sm font-medium hover:border-[#1B2D6B] transition-colors shrink-0">
            Open Now
        </button>
        <button class="px-4 py-2 rounded-full border border-gray-200 text-gray-700 text-sm font-medium hover:border-[#1B2D6B] transition-colors shrink-0">
            Express Booking
        </button>
        <button class="px-4 py-2 rounded-full border border-gray-200 text-gray-700 text-sm font-medium hover:border-[#1B2D6B] transition-colors shrink-0">
            Rating
        </button>
    </div>
</div>

{{-- ───── LIST + MAP ──────────────────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-6 py-6">
    <div class="flex gap-6">

        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-500 mb-4">
                <span class="font-semibold text-gray-800">{{ $salons->total() }}</span>
                salons offering {{ $heading ?? 'this treatment' }}
            </p>

            <div class="divide-y divide-gray-100">
                @forelse ($salons as $salon)
                    <x-salon-card :salon="$salon" layout="list" />
                @empty
                    <div class="py-16 text-center">
                        <div class="text-5xl mb-4">🔍</div>
                        <p class="text-gray-500">No salons in this category yet.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $salons->links() }}
            </div>
        </div>

        <div class="hidden lg:block w-[420px] shrink-0">
            <div class="sticky" style="top: calc(var(--navbar-h, 96px) + 55px);">
                <x-leaflet-map
                    id="map-kategori"
                    height="calc(100vh - 200px)"
                    :markers="$mapMarkers"
                />
            </div>
        </div>

    </div>
</div>

</x-layouts.public>

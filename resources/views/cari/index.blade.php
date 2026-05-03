<x-layouts.public title="Search Salons">

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

{{-- ───── HERO SEARCH BAR ──────────────────────────────────────────────── --}}
<div class="bg-[#1B2D6B]">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <form action="{{ route('cari') }}" method="GET"
              class="bg-white rounded-2xl p-2 flex items-center shadow-xl max-w-3xl">
            <div class="flex-1 px-4 py-2 border-r border-gray-100">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Treatment</div>
                <input name="q" value="{{ request('q') }}" placeholder="e.g. Haircut…"
                       class="w-full text-sm outline-none text-gray-800" />
            </div>
            <div class="flex-1 px-4 py-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Location</div>
                <input name="lokasi" value="{{ request('lokasi') }}" placeholder="City or area…"
                       class="w-full text-sm outline-none text-gray-800" />
            </div>
            <div class="px-4 py-2 border-l border-gray-100">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Date</div>
                <input name="tanggal" type="date" value="{{ request('tanggal') }}"
                       class="text-sm outline-none text-gray-800" />
            </div>
            <button type="submit"
                    class="flex-shrink-0 w-12 h-12 bg-[#1B2D6B] text-white rounded-xl flex items-center justify-center hover:bg-[#4BA3CC] transition-colors mx-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </button>
        </form>
        @if(request('q') || request('lokasi'))
            <h2 class="text-white/80 text-lg mt-4">
                @if(request('q'))
                    Results for: <strong class="text-white">{{ request('q') }}</strong>
                @endif
                @if(request('lokasi'))
                    <span>in <strong class="text-white">{{ request('lokasi') }}</strong></span>
                @endif
            </h2>
        @endif
    </div>
</div>

{{-- ───── SORT CHIPS ───────────────────────────────────────────────────── --}}
<div id="sort-bar" class="bg-white border-b border-gray-100 sticky z-40 shadow-sm" style="top: var(--navbar-h, 96px);">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-2 overflow-x-auto scrollbar-hide">
        @php $currentSort = request('sort'); @endphp
        @foreach([
            ''                  => 'Most Popular',
            'rating-tertinggi'  => 'Top Rated',
            'harga-terendah'    => 'Lowest Price',
        ] as $sortKey => $label)
            <a href="{{ route('cari', array_merge(request()->query(), ['sort' => $sortKey])) }}"
               class="px-4 py-2 rounded-full border text-sm font-medium flex-shrink-0 transition-colors
                      {{ ($currentSort ?? '') === $sortKey ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>

{{-- ───── RESULTS + MAP ────────────────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-6 py-6">
    <div class="flex gap-6">

        {{-- List --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-500 mb-5">
                <span class="font-semibold text-gray-800">{{ $salons->total() }}</span> salons found
            </p>
            <div class="divide-y divide-gray-100">
                @forelse ($salons as $salon)
                    <x-salon-card :salon="$salon" layout="list" />
                @empty
                    <div class="py-16 text-center">
                        <div class="text-5xl mb-4">🔍</div>
                        <p class="text-lg text-gray-500 mb-2">No results for this search.</p>
                        <p class="text-sm text-gray-400">Try a different keyword or clear your filters.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-8">{{ $salons->links() }}</div>
        </div>

        {{-- Map --}}
        <div class="hidden lg:block w-[420px] flex-shrink-0">
            <div class="sticky" style="top: calc(var(--navbar-h, 96px) + 55px);">
                <x-leaflet-map
                    id="map-search"
                    height="calc(100vh - 200px)"
                    :markers="$mapMarkers"
                />
            </div>
        </div>

    </div>
</div>

</x-layouts.public>

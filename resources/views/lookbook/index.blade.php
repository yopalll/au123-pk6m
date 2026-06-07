<x-layouts.public title="Lookbook">
@php
    $imgUrl = fn ($url) => $url ? (\Illuminate\Support\Str::startsWith($url, ['http','//']) ? $url : asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url)) : 'https://placehold.co/600x800/1a1c1f/ffb68b?text=Lookbook';
@endphp

@if ($featured)
    <a href="{{ route('lookbook.show', $featured->slug) }}" class="block relative h-[55vh] min-h-80 overflow-hidden group">
        <img src="{{ $imgUrl($featured->cover_url) }}" alt="{{ $featured->judul }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
        <div class="absolute bottom-8 left-6 sm:left-10 text-white max-w-lg">
            <p class="text-xs uppercase tracking-widest mb-2 opacity-90">Featured · {{ $featured->tema }}</p>
            <h1 class="text-3xl sm:text-4xl font-bold mb-3" style="font-family:'DM Serif Display',serif">{{ $featured->judul }}</h1>
            <span class="inline-block px-5 py-2 bg-white text-[#1B2D6B] text-sm font-semibold rounded-full">Lihat Lookbook →</span>
        </div>
    </a>
@endif

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10" x-data="{ tema: 'all' }">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">Lookbook Skincare</h2>
    </div>

    @if ($temas->count())
        <div class="flex gap-2 overflow-x-auto pb-2 mb-6 scrollbar-hide">
            <button @click="tema='all'" :class="tema==='all' ? 'bg-[#1B2D6B] text-white' : 'border border-gray-200 text-gray-600'" class="shrink-0 px-4 py-1.5 rounded-full text-sm">Semua</button>
            @foreach ($temas as $t)
                <button @click="tema='{{ $t }}'" :class="tema==='{{ $t }}' ? 'bg-[#1B2D6B] text-white' : 'border border-gray-200 text-gray-600'" class="shrink-0 px-4 py-1.5 rounded-full text-sm whitespace-nowrap">{{ $t }}</button>
            @endforeach
        </div>
    @endif

    @if ($lookbooks->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($lookbooks as $lb)
                <a href="{{ route('lookbook.show', $lb->slug) }}"
                   x-show="tema==='all' || tema==='{{ $lb->tema }}'"
                   class="group block rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow">
                    <div class="relative aspect-[3/4] overflow-hidden">
                        <img src="{{ $imgUrl($lb->cover_url) }}" alt="{{ $lb->judul }}" loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-60"></div>
                        <div class="absolute bottom-0 p-4 text-white">
                            <span class="text-[10px] uppercase tracking-wider opacity-90">{{ $lb->tema }}</span>
                            <h3 class="font-semibold text-lg" style="font-family:'DM Serif Display',serif">{{ $lb->judul }}</h3>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">📸</p>
            <p>Belum ada lookbook. Admin Store bisa menambahkan di panel.</p>
        </div>
    @endif
</div>
</x-layouts.public>

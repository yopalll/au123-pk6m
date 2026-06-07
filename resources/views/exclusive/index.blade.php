<x-layouts.public title="Konten Eksklusif">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">Konten Eksklusif</h1>
            <p class="text-sm text-gray-500">Tier kamu: <span class="font-semibold uppercase">{{ $userTier }}</span></p>
        </div>
        <a href="{{ route('akun.poin') }}" class="text-sm text-[#4BA3CC] hover:underline">Poin →</a>
    </div>

    @if (session('error'))<div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2">{{ session('error') }}</div>@endif

    @if ($contents->count())
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($contents as $c)
                <div class="relative bg-white border border-gray-100 rounded-2xl overflow-hidden {{ $c->is_accessible ? '' : 'opacity-70' }}">
                    <div class="aspect-video bg-[#E8F4FB] flex items-center justify-center text-3xl">
                        {{ $c->tipe === 'video' ? '🎬' : ($c->tipe === 'tip' ? '💡' : '📄') }}
                    </div>
                    <div class="p-4">
                        <span class="text-[10px] uppercase tracking-wide text-[#4BA3CC] font-semibold">{{ $c->tipe }} · min {{ $c->min_tier }}</span>
                        <h3 class="font-medium text-sm mt-1 line-clamp-2">{{ $c->judul }}</h3>
                        @if ($c->is_accessible)
                            <a href="{{ route('exclusive.show', $c->slug) }}" class="inline-block mt-3 text-xs px-3 py-1.5 bg-[#1B2D6B] text-white rounded-full">Baca</a>
                        @else
                            <p class="mt-3 text-xs text-gray-400">🔒 Butuh tier {{ $c->min_tier }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">🔒</p>
            <p>Belum ada konten. Kumpulkan poin untuk membuka tier lebih tinggi!</p>
        </div>
    @endif
</div>
</x-layouts.public>

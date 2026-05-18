<x-layouts.public :title="$kategori->name ?? 'Kategori'">

{{-- ───── HERO BANNER (Treatwell-style split) ──────────────────────────── --}}
<div class="bg-[#1B2D6B] relative overflow-hidden">
    <div class="absolute right-0 top-0 bottom-0 w-1/2 bg-gradient-to-l from-[#4BA3CC]/30 to-transparent hidden md:block"></div>
    <div class="max-w-7xl mx-auto px-6 py-10 relative z-10">
        <nav class="text-xs text-white/40 mb-4 flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="hover:text-white/70">Beranda</a>
            <span>/</span>
            <span class="text-white/70">{{ $kategori->name ?? 'Kategori' }}</span>
        </nav>
        <h1 class="text-3xl md:text-4xl text-white mb-2">{{ $kategori->name ?? 'Layanan' }}</h1>
        <p class="text-white/60 text-sm">{{ $kategori->deskripsi ?? '' }}</p>
    </div>
</div>

{{-- ───── FILTER BAR ──────────────────────────────────────────────────── --}}
<div class="sticky top-[105px] z-40 bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-2 overflow-x-auto scrollbar-hide">

        {{-- Sort --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-gray-200 text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors flex-shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 6h18M7 12h10M11 18h2"/>
                </svg>
                Urutkan
                <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute top-full left-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-lg py-1 z-50">
                @foreach(['Terpopuler','Rating Tertinggi','Harga Terendah','Harga Tertinggi','Terbaru'] as $opt)
                    <a href="{{ request()->fullUrlWithQuery(['sort' => strtolower(str_replace(' ','-',$opt))]) }}"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-[#E8F4FB] hover:text-[#1B2D6B]
                              {{ request('sort') === strtolower(str_replace(' ','-',$opt)) ? 'font-semibold text-[#1B2D6B]' : '' }}">
                        {{ $opt }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Price filter --}}
        <button class="flex items-center gap-1 px-4 py-2 rounded-full border {{ request('harga') ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }} text-sm font-medium transition-colors flex-shrink-0">
            Harga
            <svg class="w-3 h-3 {{ request('harga') ? 'text-white' : '' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
        </button>

        <button class="px-4 py-2 rounded-full border {{ request('open_now') ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }} text-sm font-medium transition-colors flex-shrink-0">
            Buka Sekarang
        </button>
        <button class="px-4 py-2 rounded-full border border-gray-200 text-gray-700 text-sm font-medium hover:border-[#1B2D6B] transition-colors flex-shrink-0">
            Express Booking
        </button>
        <button class="px-4 py-2 rounded-full border border-gray-200 text-gray-700 text-sm font-medium hover:border-[#1B2D6B] transition-colors flex-shrink-0">
            Rating
        </button>
    </div>
</div>

{{-- ───── MAIN CONTENT: LIST + MAP ────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-6 py-6">
    <div class="flex gap-6">

        {{-- ── Left: Salon List ──────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-500 mb-4">
                <span class="font-semibold text-gray-800">{{ $salons->total() }}</span>
                salon menawarkan {{ $kategori->name ?? 'layanan' }} di Indonesia
            </p>

            <div class="divide-y divide-gray-100">
                @forelse ($salons as $salon)
                    <x-salon-card :salon="$salon" layout="list" />
                @empty
                    <div class="py-16 text-center">
                        <div class="text-5xl mb-4">🔍</div>
                        <p class="text-gray-500">Belum ada salon untuk kategori ini.</p>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $salons->links() }}
            </div>
        </div>

        {{-- ── Right: Map Panel ──────────────────────────────────────── --}}
        <div class="hidden lg:block w-[420px] flex-shrink-0">
            <div class="sticky top-[160px] h-[calc(100vh-200px)] rounded-2xl bg-[#E8F4FB] border border-[#C5E1F0] overflow-hidden flex flex-col items-center justify-center gap-3 text-gray-400">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <p class="text-sm font-medium">Peta interaktif</p>
                <p class="text-xs text-center max-w-[200px]">Integrasikan dengan Google Maps / Leaflet.js di sini</p>
                <button class="mt-2 px-4 py-2 bg-[#1B2D6B] text-white text-xs font-semibold rounded-full">
                    Tampilkan Peta
                </button>
            </div>
        </div>

    </div>
</div>

</x-layouts.public>

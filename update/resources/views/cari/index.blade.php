<x-layouts.public title="Cari Salon">

{{-- ───── HERO SEARCH BAR ──────────────────────────────────────────────── --}}
<div class="bg-[#1B2D6B]">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <form action="{{ route('cari') }}" method="GET"
              class="bg-white rounded-2xl p-2 flex items-center shadow-xl max-w-3xl">
            <div class="flex-1 px-4 py-2 border-r border-gray-100">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Layanan</div>
                <input name="q" value="{{ request('q') }}" placeholder="mis. Potong Rambut…"
                       class="w-full text-sm outline-none text-gray-800" />
            </div>
            <div class="flex-1 px-4 py-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lokasi</div>
                <input name="lokasi" value="{{ request('lokasi') }}" placeholder="Kota atau daerah…"
                       class="w-full text-sm outline-none text-gray-800" />
            </div>
            <div class="px-4 py-2 border-l border-gray-100">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal</div>
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
        @if(request('q'))
            <h2 class="text-white/80 text-lg mt-4">
                Hasil pencarian: <strong class="text-white">{{ request('q') }}</strong>
                @if(request('lokasi')) <span>di <strong>{{ request('lokasi') }}</strong></span> @endif
            </h2>
        @endif
    </div>
</div>

{{-- ───── FILTER CHIPS ─────────────────────────────────────────────────── --}}
<div class="bg-white border-b border-gray-100 sticky top-[105px] z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 py-3 flex items-center gap-2 overflow-x-auto scrollbar-hide">
        @foreach(['Terpopuler','Top Rated','Terdekat','Harga Terjangkau','Buka Sekarang','Last Minute'] as $f)
            <button class="px-4 py-2 rounded-full border text-sm font-medium flex-shrink-0 transition-colors
                           {{ request('filter') === $f ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }}">
                {{ $f }}
            </button>
        @endforeach
    </div>
</div>

{{-- ───── RESULTS + MAP ────────────────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-6 py-6">
    <div class="flex gap-6">

        {{-- List --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-500 mb-5">
                <span class="font-semibold text-gray-800">{{ $salons->total() }}</span> salon ditemukan
            </p>
            <div class="divide-y divide-gray-100">
                @forelse ($salons as $salon)
                    <x-salon-card :salon="$salon" layout="list" />
                @empty
                    <div class="py-16 text-center">
                        <div class="text-5xl mb-4">🔍</div>
                        <p class="text-lg text-gray-500 mb-2">Tidak ada hasil untuk pencarian ini.</p>
                        <p class="text-sm text-gray-400">Coba kata kunci lain atau hapus filter.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-8">{{ $salons->links() }}</div>
        </div>

        {{-- Map --}}
        <div class="hidden lg:block w-[420px] flex-shrink-0">
            <div class="sticky top-[160px] h-[calc(100vh-200px)] rounded-2xl bg-[#E8F4FB] border border-[#C5E1F0] overflow-hidden flex flex-col items-center justify-center gap-3 text-gray-400">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <p class="text-sm font-medium">Peta Salon</p>
                <p class="text-xs text-center max-w-[200px]">Integrasi Google Maps / Leaflet di sini</p>
                <button class="mt-2 px-4 py-2 bg-[#1B2D6B] text-white text-xs font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                    Tampilkan Peta
                </button>
            </div>
        </div>

    </div>
</div>

</x-layouts.public>

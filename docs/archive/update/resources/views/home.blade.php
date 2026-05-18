<x-layouts.public title="Beranda">

{{-- ───── HERO ───────────────────────────────────────────────────────────── --}}
<section class="relative bg-[#1B2D6B] overflow-hidden min-h-[480px] flex items-center">
    {{-- Background grid --}}
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:48px 48px"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-[#4BA3CC]/20 to-transparent"></div>

    <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 text-center">
        <div class="inline-block bg-[#4BA3CC]/20 text-[#4BA3CC] border border-[#4BA3CC]/30 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest mb-5">
            ✦ Library Salon Indonesia
        </div>
        <h1 class="text-5xl md:text-6xl text-white mb-4 leading-tight">
            Temukan Salon<br /><em class="text-[#4BA3CC]">Terbaik</em> di Sekitarmu
        </h1>
        <p class="text-white/60 text-lg mb-10 font-light">Lebih dari 2.400 salon profesional siap melayanimu</p>

        {{-- Search card --}}
        <form action="{{ route('cari') }}" method="GET"
              class="bg-white rounded-2xl p-2 flex items-center gap-0 shadow-2xl max-w-2xl mx-auto">
            <div class="flex-1 px-4 py-2 border-r border-gray-100">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Layanan</div>
                <input name="q" placeholder="mis. Potong, Manikur, Pijat…"
                       class="w-full text-sm outline-none text-gray-800 placeholder-gray-300" />
            </div>
            <div class="flex-1 px-4 py-2">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Lokasi</div>
                <input name="lokasi" placeholder="mis. Jakarta Selatan…"
                       class="w-full text-sm outline-none text-gray-800 placeholder-gray-300" />
            </div>
            <button type="submit"
                    class="flex-shrink-0 w-12 h-12 bg-[#1B2D6B] text-white rounded-xl flex items-center justify-center hover:bg-[#4BA3CC] transition-colors mx-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </button>
        </form>

        {{-- Quick chips --}}
        <div class="flex flex-wrap gap-2 justify-center mt-5">
            @foreach(['Potong Rambut','Manikur','Facial','Pijat','Alis','Makeup'] as $chip)
                <a href="{{ route('cari', ['q' => $chip]) }}"
                   class="bg-white/10 hover:bg-[#4BA3CC]/20 border border-white/15 text-white/80 hover:text-white text-xs rounded-full px-4 py-1.5 transition-all">
                    {{ $chip }}
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ───── STATS BAR ───────────────────────────────────────────────────────── --}}
<div class="bg-[#E8F4FB] border-b border-[#C5E1F0]">
    <div class="max-w-7xl mx-auto px-6 py-5 flex flex-wrap justify-center gap-10">
        @foreach([['2.400+','Salon Terdaftar'],['850rb+','Booking Berhasil'],['4.8★','Rating Rata-rata'],['150+','Kota']] as [$n,$l])
            <div class="text-center">
                <div class="text-2xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $n }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $l }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ───── KATEGORI ─────────────────────────────────────────────────────────── --}}
<section class="max-w-7xl mx-auto px-6 py-12">
    <h2 class="text-2xl text-[#1B2D6B] mb-6">Jelajahi Kategori</h2>
    <div class="flex gap-5 overflow-x-auto pb-2 scrollbar-hide">
        @foreach([
            ['rambut','💇','Rambut'],['facial','🧖','Facial'],
            ['pijat','💆','Pijat'],['kuku','💅','Kuku'],
            ['alis','🤨','Alis'],['makeup','💄','Makeup'],
            ['tubuh','🛁','Tubuh'],['pria','🪒',"Pria's"],
        ] as [$slug,$emoji,$label])
            <a href="{{ route('kategori.show', $slug) }}"
               class="flex-shrink-0 flex flex-col items-center gap-2 group">
                <div class="w-16 h-16 rounded-full bg-[#E8F4FB] border-2 border-[#C5E1F0] flex items-center justify-center text-2xl
                             group-hover:bg-[#1B2D6B] group-hover:border-[#1B2D6B] group-hover:-translate-y-1 transition-all duration-200">
                    {{ $emoji }}
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-[#1B2D6B]">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ───── SALON TERPOPULER ──────────────────────────────────────────────────── --}}
<section class="max-w-7xl mx-auto px-6 pb-12">
    <div class="flex items-baseline justify-between mb-6">
        <h2 class="text-2xl text-[#1B2D6B]">Salon Terpopuler</h2>
        <a href="{{ route('cari') }}" class="text-sm text-[#4BA3CC] font-medium hover:underline">Lihat semua →</a>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse ($salons ?? [] as $salon)
            <x-salon-card :salon="$salon" layout="list" />
        @empty
            <p class="text-gray-400 py-8 text-center">Belum ada salon terdaftar.</p>
        @endforelse
    </div>
</section>

{{-- ───── CARA KERJA ────────────────────────────────────────────────────────── --}}
<section class="bg-gray-50 py-16">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-2">Cara Kerja</div>
        <h2 class="text-3xl text-[#1B2D6B] mb-10">Booking dalam 3 Langkah</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['🔍','01','Temukan Salon','Cari berdasarkan layanan, lokasi, atau rating. Filter sesuai kebutuhanmu.'],
                ['📅','02','Pilih Waktu','Lihat ketersediaan real-time dan pilih jadwal yang paling cocok.'],
                ['✨','03','Nikmati Layanan','Datang ke salon, layanan sudah terjadwal. Bayar langsung di tempat.'],
            ] as [$icon,$n,$title,$desc])
                <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="w-14 h-14 rounded-full bg-[#E8F4FB] border-2 border-[#C5E1F0] flex items-center justify-center text-2xl mx-auto mb-4">
                        {{ $icon }}
                    </div>
                    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider mb-2">Langkah {{ $n }}</div>
                    <h3 class="text-xl text-[#1B2D6B] mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ───── CTA ───────────────────────────────────────────────────────────────── --}}
<section class="bg-[#1B2D6B] py-20 text-center">
    <h2 class="text-4xl text-white mb-4">Siap Tampil Terbaik?</h2>
    <p class="text-white/60 text-lg mb-8">Bergabung bersama 850.000+ pengguna VIYGO di seluruh Indonesia</p>
    <div class="flex gap-3 justify-center flex-wrap">
        <a href="{{ route('cari') }}"
           class="px-8 py-3 bg-white text-[#1B2D6B] font-semibold rounded-full hover:bg-[#E8F4FB] transition-colors">
            Booking Sekarang
        </a>
        <a href="{{ route('mitra') }}"
           class="px-8 py-3 border-2 border-white/30 text-white font-medium rounded-full hover:border-white hover:bg-white/5 transition-all">
            Daftarkan Salon Anda
        </a>
    </div>
</section>

</x-layouts.public>

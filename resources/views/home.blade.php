<x-layouts.public title="Home">
@php
    $venueImg = fn ($s) => $s->image_url
        ? (\Illuminate\Support\Str::startsWith($s->image_url, ['http','//']) ? $s->image_url : asset($s->image_url))
        : 'https://placehold.co/600x400/1a1c1f/ffb68b?text=' . urlencode($s->nama_salon);
    $treatments = [
        ['hair','content_cut','Hair'], ['nail','back_hand','Nails'], ['facial','face_retouching_natural','Face'], ['massage','spa','Massage'],
        ['brow','visibility','Brows'], ['body','accessibility_new','Body'], ['waxing','water_drop','Waxing'], ['men','face','Men\'s'],
    ];
@endphp

{{-- ───── HERO ─────────────────────────────────────────────────────────────── --}}
<section class="relative min-h-[68vh] flex flex-col justify-center items-center px-5 md:px-20 py-24 text-center">
    <div class="absolute inset-0 z-0 pointer-events-none bg-gradient-to-b from-[#111316]/10 via-[#111316]/40 to-[#111316]"></div>

    <h1 class="relative z-10 text-5xl md:text-6xl text-[#e2e2e6] mb-10 max-w-4xl leading-[1.1]" style="font-family:'Playfair Display',serif">
        Book beauty &amp; wellness near you
    </h1>

    <form action="{{ route('cari') }}" method="GET"
          class="relative z-10 w-full max-w-4xl glass-surface rounded-2xl p-2 flex flex-col md:flex-row gap-2 shadow-2xl">
        <div class="flex-1 flex items-center bg-[#1a1c1f] rounded-xl px-4 py-3">
            <span class="material-symbols-outlined text-white/40 mr-3">search</span>
            <input name="q" class="w-full bg-transparent border-none outline-none text-[#e2e2e6] placeholder-white/35" placeholder="Treatment, salon, atau produk" />
        </div>
        <div class="flex-1 flex items-center bg-[#1a1c1f] rounded-xl px-4 py-3">
            <span class="material-symbols-outlined text-white/40 mr-3">location_on</span>
            <input name="lokasi" class="w-full bg-transparent border-none outline-none text-[#e2e2e6] placeholder-white/35" placeholder="Lokasi kamu" />
        </div>
        <button type="submit" class="bg-[#ffb68b] hover:bg-[#ffdbc8] text-[#3a1d08] font-semibold px-10 py-4 rounded-xl transition-all">
            Search
        </button>
    </form>
</section>

{{-- ───── POPULAR TREATMENTS ───────────────────────────────────────────────── --}}
<section class="relative z-10 max-w-6xl mx-auto px-5 md:px-20 py-16">
    <h2 class="text-3xl md:text-4xl text-[#e2e2e6] mb-12 text-center" style="font-family:'Playfair Display',serif">Popular Treatments</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 max-w-4xl mx-auto">
        @foreach ($treatments as [$q, $icon, $label])
            <a href="{{ route('cari', ['q' => $q]) }}"
               class="glass-surface rounded-2xl aspect-square p-6 flex flex-col items-center justify-center gap-4 hover:border-[#ffb68b]/30 transition-all group">
                <div class="w-16 h-16 rounded-full bg-[#1e2023]/60 flex items-center justify-center group-hover:bg-[#ffb68b]/10 transition-colors">
                    <span class="material-symbols-outlined text-[#a5cbea] group-hover:text-[#ffb68b] transition-colors" style="font-size:30px">{{ $icon }}</span>
                </div>
                <span class="text-sm font-medium text-[#e2e2e6] tracking-wide">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ───── PARTNERS (marquee) ──────────────────────────────────────────────── --}}
@php
    // [file, nama, warna-fallback]. File logo: public/images/partners/<file>
    // Jika file hilang, otomatis tampil wordmark berwarna (tidak rusak).
    $partners = [
        ['shopee.png',     'Shopee',     '#ee4d2d'],
        ['tokopedia.png',  'Tokopedia',  '#42b549'],
        ['ovo.png',        'OVO',        '#4c2a86'],
        ['dana.png',       'DANA',       '#118eea'],
        ['midtrans.jpg',   'Midtrans',   '#16314f'],
        ['blibli.svg',     'Blibli',     '#0095da'],
        ['lazada.svg',     'Lazada',     '#1a0dab'],
        ['belajarkuy.png', 'BelajarKUY', '#3d1a78'],
    ];
@endphp
<section class="relative z-10 py-16">
    <p class="text-center text-xs uppercase tracking-[0.25em] text-white/40 mb-8">Partner &amp; Pembayaran Terpercaya</p>

    <style>
        .partner-marquee { overflow: hidden; -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent); mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent); }
        .partner-track { display: flex; width: max-content; gap: 18px; animation: partner-scroll 32s linear infinite; }
        .partner-marquee:hover .partner-track { animation-play-state: paused; }
        @keyframes partner-scroll { from { transform: translateX(0); } to { transform: translateX(calc(-50% - 9px)); } }
        @media (prefers-reduced-motion: reduce) { .partner-track { animation: none; } }
        .partner-chip { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; height: 72px; min-width: 180px; padding: 0 30px; border-radius: 16px; background: #ffffff; border: 1px solid rgba(255,255,255,0.08); transition: transform .25s, box-shadow .25s; }
        .partner-chip:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,0.35); }
        .partner-chip img { max-height: 36px; max-width: 130px; width: auto; object-fit: contain; }
        .partner-chip span { font-family: 'Manrope', sans-serif; font-weight: 800; font-size: 22px; letter-spacing: -0.01em; }
    </style>

    <div class="partner-marquee">
        <div class="partner-track">
            {{-- Daftar digandakan 2x agar loop mulus --}}
            @foreach (array_merge($partners, $partners) as [$file, $name, $color])
                <div class="partner-chip" aria-hidden="{{ $loop->index >= count($partners) ? 'true' : 'false' }}">
                    <img src="{{ asset('images/partners/'.$file) }}" alt="{{ $name }}"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';">
                    <span style="color: {{ $color }}; display:none;">{{ $name }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ───── FEATURED VENUES ─────────────────────────────────────────────────── --}}
<section class="relative z-10 max-w-6xl mx-auto px-5 md:px-20 py-16">
    <div class="flex justify-between items-end mb-10">
        <h2 class="text-3xl md:text-4xl text-[#e2e2e6]" style="font-family:'Playfair Display',serif">Featured Venues</h2>
        <a href="{{ route('cari') }}" class="flex items-center gap-1 text-sm text-white/50 hover:text-[#ffb68b] transition-colors">
            View all <span class="material-symbols-outlined" style="font-size:18px">arrow_forward</span>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @forelse (($salons ?? collect())->take(3) as $salon)
            <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
               class="group glass-surface p-4 rounded-3xl border-transparent hover:border-[#ffb68b]/30 transition-colors">
                <div class="relative w-full h-60 rounded-2xl overflow-hidden mb-5">
                    <img src="{{ $venueImg($salon) }}" alt="{{ $salon->nama_salon }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                    @if ($salon->rating > 0)
                        <div class="absolute top-3 right-3 bg-[#111316]/80 backdrop-blur-md px-3 py-1 rounded-full flex items-center gap-1">
                            <span class="material-symbols-outlined text-[#ffb68b]" style="font-size:14px;font-variation-settings:'FILL' 1">star</span>
                            <span class="text-xs text-[#e2e2e6]">{{ number_format($salon->rating, 1) }}</span>
                        </div>
                    @endif
                </div>
                <div class="text-center px-2 pb-2">
                    <h3 class="text-2xl text-[#e2e2e6] mb-1" style="font-family:'Playfair Display',serif">{{ $salon->nama_salon }}</h3>
                    <p class="flex items-center justify-center text-white/50 text-sm mb-4">
                        <span class="material-symbols-outlined mr-1" style="font-size:18px">location_on</span>
                        {{ $salon->kota->nama ?? 'Indonesia' }}
                    </p>
                    <div class="flex justify-between items-end border-t border-white/10 pt-4">
                        <p class="text-white/45 text-xs">{{ $salon->total_review ?? 0 }} ulasan</p>
                        <p class="text-sm text-[#ffdbc8]">Lihat salon →</p>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-white/40 py-8 text-center col-span-3">Belum ada salon.</p>
        @endforelse
    </div>
</section>

{{-- ───── DUAL CTA (Salon vs Shop) ────────────────────────────────────────── --}}
<section class="relative z-10 max-w-6xl mx-auto px-5 md:px-20 py-4 grid md:grid-cols-2 gap-6">
    <a href="{{ route('cari') }}" class="group glass-surface rounded-2xl p-7 min-h-44 flex flex-col justify-end hover:border-[#a5cbea]/30 transition-colors">
        <p class="text-[11px] uppercase tracking-[0.2em] text-[#a5cbea] mb-2">Beauty Salon</p>
        <h3 class="text-2xl text-[#e2e2e6] mb-1" style="font-family:'Playfair Display',serif">Booking treatment</h3>
        <p class="text-sm text-white/50">Cari & pesan salon terbaik · <span class="text-[#ffdbc8] group-hover:underline">Jelajahi →</span></p>
    </a>
    <a href="{{ route('shop.index') }}" class="group glass-surface rounded-2xl p-7 min-h-44 flex flex-col justify-end hover:border-[#ffb68b]/30 transition-colors">
        <p class="text-[11px] uppercase tracking-[0.2em] text-[#ffdbc8] mb-2">Skincare Shop</p>
        <h3 class="text-2xl text-[#e2e2e6] mb-1" style="font-family:'Playfair Display',serif">Produk skincare premium</h3>
        <p class="text-sm text-white/50">Serum, moisturizer, & lainnya · <span class="text-[#ffdbc8] group-hover:underline">Belanja →</span></p>
    </a>
</section>

{{-- ───── HOW IT WORKS ──────────────────────────────────────────────────────── --}}
<section class="relative z-10 px-5 md:px-20 py-20 text-center">
    <h2 class="text-3xl md:text-4xl text-[#e2e2e6] mb-3" style="font-family:'Playfair Display',serif">How it works</h2>
    <p class="text-white/50 max-w-xl mx-auto mb-16">Temukan & pesan perawatan kecantikan berikutnya dalam tiga langkah sederhana.</p>

    <div class="relative grid grid-cols-1 md:grid-cols-3 gap-12 md:gap-8 max-w-5xl mx-auto">
        <div class="hidden md:block absolute top-12 left-[16.66%] right-[16.66%] h-px bg-white/10 -z-0"></div>
        @foreach ([
            ['search','1. Search','Cari salon & produk skincare berdasarkan treatment atau lokasi.','text-[#a5cbea]', ''],
            ['calendar_month','2. Book','Pilih waktu & pesan instan online, atau checkout produk 24/7.','text-[#ffb68b]','border border-[#ffb68b]/20'],
            ['mood','3. Enjoy','Datang & nikmati perawatan premium, atau terima produkmu di rumah.','text-[#abcdcd]',''],
        ] as [$icon,$title,$desc,$iconColor,$ring])
            <div class="relative flex flex-col items-center">
                <div class="w-24 h-24 rounded-full glass-surface flex items-center justify-center mb-7 {{ $ring }}">
                    <span class="material-symbols-outlined {{ $iconColor }}" style="font-size:38px">{{ $icon }}</span>
                </div>
                <h3 class="text-2xl text-[#e2e2e6] mb-3" style="font-family:'Playfair Display',serif">{{ $title }}</h3>
                <p class="text-sm text-white/50 px-4 max-w-xs">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</section>

</x-layouts.public>

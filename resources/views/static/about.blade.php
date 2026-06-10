<x-layouts.public title="Tentang VIYGO">

@php
    // ─── Fitur Utama VIYGO ────────────────────────────────────────────────
    $features = [
        ['storefront',          'Marketplace Salon',      'Temukan & pesan layanan kecantikan dari salon terverifikasi di sekitarmu, lengkap dengan profil, harga, dan ulasan.'],
        ['event_available',     'Booking Real-time',      'Pilih layanan, jadwal, dan stylist dengan ketersediaan slot yang ditampilkan secara langsung.'],
        ['payments',            'Pembayaran Terintegrasi','Checkout aman via Midtrans Snap — Virtual Account, GoPay, OVO, hingga kartu kredit/debit.'],
        ['shopping_bag',        'E-commerce Skincare',    'Belanja produk skincare original dengan keranjang, wishlist, dan Skincare Finder sesuai tipe kulitmu.'],
        ['forum',               'Komunitas & Forum',      'Diskusi, tanya-jawab, like, bookmark, leaderboard, dan poin komunitas antar pengguna.'],
        ['recycling',           'Empty Return & Poin',    'Kembalikan kemasan kosong, kumpulkan poin loyalti, dan tukar dengan konten eksklusif.'],
        ['verified_user',       'Verifikasi & Keamanan',  'Verifikasi email lewat OTP, autentikasi dua faktor (2FA), dan login Google yang aman.'],
        ['admin_panel_settings','Panel Admin & Owner',    'Dashboard Filament lengkap untuk admin platform dan pemilik salon mengelola usahanya.'],
    ];

    // ─── Stack Teknologi VIYGO (kategori => [ikon, [ [judul, deskripsi], ... ]]) ───
    $stack = [
        'Backend' => ['dns', [
            ['Laravel 12',       'Framework PHP modern — routing, middleware, Eloquent ORM, Queue, dan Mail.'],
            ['PHP 8.3',          'Fitur terbaru: enums, readonly properties, named arguments, match expressions.'],
            ['MySQL 8',          'Basis data relasional dengan transaksi ACID untuk booking, pesanan, dan poin.'],
            ['Filament 5',       'Panel admin platform & panel pemilik salon yang lengkap dan cepat dibangun.'],
            ['Livewire 4',       'Komponen interaktif dinamis tanpa harus menulis banyak JavaScript.'],
            ['Laravel Fortify',  'Backend autentikasi: login, registrasi, reset password, dan 2FA.'],
        ]],
        'Frontend' => ['brush', [
            ['Blade + Flux UI 2',           'Komponen UI premium Livewire Flux di atas templating Blade.'],
            ['Tailwind CSS 4',              'Utility-first CSS dengan design tokens kustom “Serene Floral Noir”.'],
            ['Alpine.js 3',                 'Interaktivitas ringan (dropdown, modal, countdown) langsung di markup.'],
            ['Vite 8',                      'Build tool modern — HMR instan saat dev & bundling teroptimasi untuk produksi.'],
            ['Leaflet',                     'Peta lokasi salon berbasis OpenStreetMap.'],
            ['Playfair Display + Manrope',  'Tipografi editorial mewah: serif untuk judul, sans-serif untuk teks.'],
        ]],
        'Layanan Eksternal' => ['hub', [
            ['Midtrans',          'Payment gateway — Virtual Account, GoPay, OVO, kartu kredit/debit.'],
            ['Resend',            'Email transaksional — kode OTP, verifikasi, dan konfirmasi booking/pesanan.'],
            ['Google OAuth 2.0',  'Login & daftar dengan akun Google via Laravel Socialite.'],
        ]],
        'Keamanan & Tooling' => ['shield', [
            ['OTP & 2FA',         'Verifikasi email berbasis OTP dan autentikasi dua faktor untuk keamanan akun.'],
            ['HTML Purifier',     'Sanitasi konten forum & ulasan agar aman dari serangan XSS.'],
            ['DomPDF',            'Generate invoice PDF untuk setiap booking dan pesanan.'],
            ['Laravel Queue',     'Antrian job untuk tugas asinkron seperti pengiriman email.'],
        ]],
    ];
@endphp

{{-- Hero --}}
<section class="bg-[#1B2D6B] py-20 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Kisah Kami</div>
    <h1 class="text-5xl text-white mb-4">Tentang VIYGO</h1>
    <p class="text-white/60 text-lg max-w-2xl mx-auto px-6">
        Kami bermisi membuat pemesanan layanan kecantikan &amp; wellness jadi mudah untuk semua orang.
    </p>
</section>

<div class="max-w-5xl mx-auto px-6 py-16 space-y-16">

    {{-- Mission / Story / Promise --}}
    <div class="grid md:grid-cols-3 gap-8">
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Misi Kami</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Setiap salon hebat layak ditemukan, dan setiap pelanggan layak mendapat pengalaman
                booking yang mulus. VIYGO menjembataninya dengan ketersediaan real-time, harga
                transparan, dan ulasan terverifikasi.
            </p>
        </div>
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Cerita Kami</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Berawal sebagai marketplace kecantikan, VIYGO kini menghadirkan ribuan layanan salon
                profesional sekaligus toko skincare, lookbook, dan komunitas dalam satu antarmuka elegan.
            </p>
        </div>
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Janji Kami</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Tanpa biaya tersembunyi, tanpa ulasan palsu, dan selalu ada manusia sungguhan di balik
                setiap bantuan. Kami menang saat salon dan pelanggannya sama-sama puas.
            </p>
        </div>
    </div>

    {{-- ═══════════ Fitur Utama ═══════════ --}}
    <section>
        <h2 class="text-3xl text-[#1B2D6B] text-center mb-2">Fitur Utama</h2>
        <p class="text-center text-sm text-gray-500 mb-10">Semua yang kamu butuhkan untuk merawat diri, dalam satu platform.</p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($features as [$icon, $title, $desc])
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover:-translate-y-1 transition-transform">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4"
                         style="background:rgba(255,182,139,0.13);border:1px solid rgba(255,182,139,0.25);">
                        <span class="material-symbols-outlined" style="color:#ffb68b;font-size:22px;">{{ $icon }}</span>
                    </div>
                    <h3 class="text-base font-semibold text-gray-900 mb-1.5">{{ $title }}</h3>
                    <p class="text-[13px] text-gray-600 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ═══════════ Stack Teknologi ═══════════ --}}
    <section>
        <h2 class="text-3xl text-[#1B2D6B] text-center mb-2">Stack Teknologi</h2>
        <p class="text-center text-sm text-gray-500 mb-10">Dibangun dengan tools modern yang andal & teruji.</p>

        <div class="space-y-10">
            @foreach ($stack as $category => [$catIcon, $items])
                <div>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                             style="background:rgba(165,203,234,0.13);border:1px solid rgba(165,203,234,0.25);">
                            <span class="material-symbols-outlined" style="color:#a5cbea;font-size:20px;">{{ $catIcon }}</span>
                        </div>
                        <h3 class="text-xl text-gray-900">{{ $category }}</h3>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ($items as [$name, $desc])
                            <div class="bg-white border border-gray-100 rounded-xl p-4 flex gap-3">
                                <span class="material-symbols-outlined shrink-0 mt-0.5" style="color:#ffb68b;font-size:20px;">check_circle</span>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $name }}</div>
                                    <p class="text-[13px] text-gray-600 leading-relaxed mt-0.5">{{ $desc }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <div class="text-center pt-4">
        <a href="{{ route('cari') }}"
           class="inline-block px-8 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
            Cari salon sekarang →
        </a>
    </div>
</div>

</x-layouts.public>

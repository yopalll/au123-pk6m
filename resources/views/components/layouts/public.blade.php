<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? 'VIYGO' }} — Beauty & Wellness Marketplace</title>
    <meta name="description" content="Find and book the best beauty professionals across the UK. Hair, nails, massage, facial, brows & lashes — all in one place." />

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet" />

    {{-- Leaflet (OpenStreetMap minimaps) --}}
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ════════ VIYGO — Serene Floral Noir (dark luxury) ════════ */
        :root {
            --sfn-bg:            #111316;
            --sfn-surface:       #1e2023;
            --sfn-surface-2:     #282a2d;
            --sfn-surface-3:     #333538;
            --sfn-outline:       #37393d;
            --sfn-outline-soft:  #2a2c30;
            --sfn-on:            #e2e2e6;
            --sfn-on-soft:       #b8b8c0;
            --sfn-on-faint:      #85858d;
            --sfn-primary:       #ffdbc8;   /* peach */
            --sfn-primary-cont:  #ffb68b;
            --sfn-on-primary:    #3a1d08;
            --sfn-secondary:     #a5cbea;   /* soft blue */

            /* legacy brand vars remapped → dark theme */
            --viygo-navy:     #ffb68b;
            --viygo-blue:     #ffdbc8;
            --viygo-blue-lt:  #282a2d;
            --viygo-blue-mid: #333538;
        }

        html, body { background: var(--sfn-bg); color: var(--sfn-on); }
        body { font-family: 'Manrope', sans-serif; }
        h1, h2, h3, h4, .font-playfair,
        [style*="DM Serif Display"], [style*="Playfair Display"] {
            font-family: 'Playfair Display', serif !important;
            letter-spacing: -0.01em;
        }

        /* ─── Midnight floral tapestry (fixed) ─── */
        body::before {
            content: ""; position: fixed; inset: 0; z-index: -1; pointer-events: none;
            background:
                linear-gradient(rgba(17,19,22,0.86), rgba(17,19,22,0.9)),
                url('{{ asset('images/floral-noir.png') }}');
            background-size: cover; background-position: center; background-attachment: fixed;
        }

        [x-cloak] { display: none !important; }

        /* ─── Glassmorphism utility (dari mockup) ─── */
        .glass-surface {
            background: rgba(30, 32, 35, 0.4);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal; font-style: normal; line-height: 1;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            -webkit-font-feature-settings: 'liga'; -webkit-font-smoothing: antialiased;
            user-select: none;
        }

        .viygo-logo-wrap { position: relative; width: 110px; height: 38px; display: block; }
        .viygo-logo-wrap img {
            position: absolute; top: 0; left: 0;
            height: 38px; width: auto; max-width: 110px;
            object-fit: contain;
            transition: opacity 0.8s ease;
            filter: brightness(0) invert(1);   /* logo putih di latar gelap */
        }
        .viygo-logo-wrap img.logo-hidden  { opacity: 0; }
        .viygo-logo-wrap img.logo-visible { opacity: 1; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--sfn-bg); }
        ::-webkit-scrollbar-thumb { background: var(--sfn-surface-3); border-radius: 99px; }

        .cat-nav-link { position: relative; padding-bottom: 14px; }
        .cat-nav-link::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 2px; background: var(--sfn-primary);
            transform: scaleX(0); transition: transform 0.2s;
        }
        .cat-nav-link:hover::after, .cat-nav-link.active::after { transform: scaleX(1); }

        /* ─── Surfaces (glassmorphism) ─── */
        .bg-white {
            background-color: rgba(30,32,35,0.55) !important;
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.07);
        }
        [class*="bg-white/"] { background-color: rgba(40,42,45,.6) !important; backdrop-filter: blur(12px); }
        /* body kebetulan punya class bg-white → backdrop-filter di root bikin containing-block
           sehingga elemen position:fixed (mis. #auth-modal) nempel ke tinggi dokumen, bukan viewport. */
        body.bg-white { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; box-shadow: none !important; }
        .bg-gray-50, [class*="bg-[#F7FAFC]"] { background-color: rgba(26,28,31,0.7) !important; }
        .bg-gray-100 { background-color: var(--sfn-surface-2) !important; }
        .bg-gray-200 { background-color: var(--sfn-surface-3) !important; }
        [class*="bg-[#E8F4FB]"], [class*="bg-[#C5E1F0]"] { background-color: rgba(165,203,234,0.10) !important; }

        /* ─── Header: frosted glass ─── */
        header.sticky {
            background-color: rgba(17,19,22,0.72) !important;
            backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
            box-shadow: 0 1px 0 rgba(255,255,255,0.06) !important;
        }
        header.sticky .border-gray-100, header.sticky .border-gray-200 { border-color: rgba(255,255,255,0.06) !important; }

        /* ─── Borders ─── */
        .border-gray-50, .border-gray-100 { border-color: var(--sfn-outline-soft) !important; }
        .border-gray-200, .border-gray-300 { border-color: var(--sfn-outline) !important; }

        /* ─── Text ─── */
        .text-gray-900 { color: var(--sfn-on) !important; }
        .text-gray-700, .text-gray-800 { color: #d4d4da !important; }
        .text-gray-600 { color: var(--sfn-on-soft) !important; }
        .text-gray-500 { color: #9a9aa2 !important; }
        .text-gray-400, .text-gray-300 { color: var(--sfn-on-faint) !important; }
        [class*="text-[#1B2D6B]"], [class*="text-[#4BA3CC]"] { color: var(--sfn-primary) !important; }

        /* ─── Primary buttons / accents (navy bg → peach, teks gelap) ─── */
        [class*="bg-[#1B2D6B]"] { background-color: var(--sfn-primary-cont) !important; color: var(--sfn-on-primary) !important; }
        [class*="bg-[#1B2D6B]"] * { color: var(--sfn-on-primary) !important; }
        [class*="hover:bg-[#4BA3CC]"]:hover { background-color: var(--sfn-primary) !important; }

        /* ─── Semantic soft backgrounds → subtle dark tints ─── */
        .bg-emerald-50 { background-color: rgba(16,185,129,.12) !important; }
        .bg-emerald-100 { background-color: rgba(16,185,129,.18) !important; }
        .bg-amber-50  { background-color: rgba(245,158,11,.12) !important; }
        .bg-red-50    { background-color: rgba(239,68,68,.12) !important; }
        .bg-blue-50   { background-color: rgba(99,102,241,.12) !important; }
        .bg-amber-100, .bg-blue-100, .bg-red-100 { background-color: var(--sfn-surface-2) !important; }

        /* ─── Hero gradients → dark + copper glass ─── */
        [class*="from-[#1B2D6B]"], [class*="from-emerald-600"], [class*="from-emerald-500"] {
            background-image: linear-gradient(135deg, rgba(255,182,139,0.16), rgba(165,203,234,0.05)) !important;
            background-color: var(--sfn-surface) !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
        }
        [class*="from-[#1B2D6B]"] *, [class*="from-emerald-600"] * { color: var(--sfn-on) !important; }
        [class*="from-[#1B2D6B]"] .text-\[\#1B2D6B\], [class*="from-emerald-600"] [class*="text-emerald-700"] { color: var(--sfn-on-primary) !important; }

        /* ─── Inputs ─── */
        input, select, textarea { color: var(--sfn-on); }
        input::placeholder, textarea::placeholder { color: var(--sfn-on-faint); }
        select option { background: var(--sfn-surface); color: var(--sfn-on); }

        /* Leaflet below sticky navbar */
        .leaflet-container { z-index: 0; background: var(--sfn-surface) !important; }
        .leaflet-pane, .leaflet-top, .leaflet-bottom { z-index: 1; }
    </style>

    {{ $head ?? '' }}
</head>
<body class="bg-white text-gray-900 antialiased">

    <x-viygo-navbar />

    {{-- Measure actual navbar height and expose as --navbar-h CSS variable --}}
    <script>
        (function () {
            function setNavbarHeight() {
                const h = document.querySelector('header');
                if (h) {
                    document.documentElement.style.setProperty('--navbar-h', h.offsetHeight + 'px');
                }
            }
            setNavbarHeight();
            window.addEventListener('resize', setNavbarHeight);
        })();
    </script>

    <main>
        {{ $slot }}
    </main>

    <x-viygo-footer />

    {{-- Spacer agar konten tidak tertutup bottom tab bar di mobile --}}
    <div class="h-14 md:hidden"></div>

    {{-- V2: Mobile bottom tab bar (Home · Shop · Lookbook · Forum · Akun) --}}
    <x-mobile-bottom-nav />

    {{-- Leaflet JS (must load before per-page map init scripts) --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    {{-- Global image fallback: broken images → placeholder (data dummy belum punya file gambar) --}}
    <script>
    (function () {
        const PH = 'https://placehold.co/400x400/1a1c1f/ffb68b?text=VIYGO';
        document.addEventListener('error', function (e) {
            const img = e.target;
            if (img && img.tagName === 'IMG' && img.dataset.fb !== '1' && !img.src.includes('placehold.co')) {
                img.dataset.fb = '1';
                img.src = PH;
            }
        }, true); // capture — fires for img load errors
    })();
    </script>

    {{-- Modal "Daftarkan akun dulu" untuk guest (inline-styled agar tak bisa di-override) --}}
    <div id="auth-modal" aria-modal="true" role="dialog"
         style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,0.78);"
         data-auth-close>
        <div style="background:#1a1c1f;border:1px solid rgba(255,255,255,0.12);border-radius:24px;padding:34px 30px;max-width:380px;width:100%;text-align:center;box-shadow:0 24px 70px rgba(0,0,0,0.65);"
             onclick="event.stopPropagation()">
            <div style="width:56px;height:56px;border-radius:9999px;background:rgba(255,182,139,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <span class="material-symbols-outlined" style="font-size:28px;color:#ffb68b;">lock</span>
            </div>
            <h3 style="font-family:'Playfair Display',serif;font-size:26px;color:#e2e2e6;margin-bottom:8px;">Daftarkan akun dulu</h3>
            <p style="font-size:14px;color:rgba(255,255,255,0.55);margin-bottom:24px;">Masuk atau buat akun untuk menambahkan produk ke keranjang &amp; wishlist.</p>
            <a href="{{ route('login') }}" style="display:block;padding:12px;background:#ffb68b;color:#3a1d08;font-size:14px;font-weight:600;border-radius:9999px;margin-bottom:10px;">Masuk</a>
            <a href="{{ route('register') }}" style="display:block;padding:12px;border:1px solid rgba(165,203,234,0.4);color:#a5cbea;font-size:14px;font-weight:600;border-radius:9999px;">Daftar Akun Baru</a>
        </div>
    </div>

    {{-- VIYGO Shop: wishlist toggle + add-to-cart + toast (global, guarded) --}}
    <script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const IS_AUTH = {{ auth()->check() ? 'true' : 'false' }};
        const authModal = document.getElementById('auth-modal');

        function showAuthModal() { if (authModal) authModal.style.display = 'flex'; }
        function hideAuthModal() { if (authModal) authModal.style.display = 'none'; }
        authModal?.addEventListener('click', e => { if (e.target.hasAttribute('data-auth-close')) hideAuthModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') hideAuthModal(); });

        function toast(msg) {
            const t = document.createElement('div');
            t.textContent = msg;
            t.className = 'fixed bottom-20 md:bottom-6 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-5 py-2.5 rounded-full shadow-xl text-sm z-[60]';
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2500);
        }
        // Wishlist toggle
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.wishlist-btn');
            if (!btn) return;
            e.preventDefault();
            if (!IS_AUTH) { showAuthModal(); return; }
            fetch('{{ route('shop.wishlist.toggle') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ id_product: btn.dataset.product })
            }).then(r => r.json()).then(d => {
                const h = btn.querySelector('.heart');
                if (h) { h.textContent = d.wishlisted ? '♥' : '♡'; h.classList.toggle('text-red-500', d.wishlisted); h.classList.toggle('text-gray-300', !d.wishlisted); }
                toast(d.wishlisted ? 'Ditambahkan ke wishlist ♥' : 'Dihapus dari wishlist');
            }).catch(showAuthModal);
        });
        // Add to cart
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.add-to-cart');
            if (!btn) return;
            e.preventDefault();
            if (!IS_AUTH) { showAuthModal(); return; }
            fetch('{{ route('shop.cart.add') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ id_product: btn.dataset.product, qty: parseInt(btn.dataset.qty || '1', 10) })
            }).then(r => { if (!r.ok) throw 0; return r.json(); }).then(d => {
                const badge = document.getElementById('nav-cart-count');
                if (badge && d.cart_count != null) { badge.textContent = d.cart_count > 99 ? '99+' : d.cart_count; badge.classList.remove('hidden'); }
                toast('Ditambahkan ke keranjang 🛒');
            }).catch(showAuthModal);
        });

        // Cegah double-submit: nonaktifkan tombol submit pada form non-GET
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || (form.method && form.method.toLowerCase() === 'get')) return;
            const btn = form.querySelector('button[type=submit], button:not([type])');
            if (btn && !btn.disabled) {
                const original = btn.dataset.loadingText || btn.innerHTML;
                setTimeout(() => {
                    btn.disabled = true;
                    btn.classList.add('opacity-70', 'cursor-wait');
                    if (btn.dataset.loadingText) btn.innerHTML = btn.dataset.loadingText;
                }, 0);
                // Re-enable jika halaman gagal navigasi (mis. validasi gagal & kembali via back)
                window.addEventListener('pageshow', () => { btn.disabled = false; btn.classList.remove('opacity-70','cursor-wait'); btn.innerHTML = original; }, { once: true });
            }
        });
    })();
    </script>

    {{ $scripts ?? '' }}
</body>
</html>

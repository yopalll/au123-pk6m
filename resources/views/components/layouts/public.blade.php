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
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    {{-- Leaflet (OpenStreetMap minimaps) --}}
    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="" />

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --viygo-navy:      #1B2D6B;
            --viygo-blue:      #4BA3CC;
            --viygo-blue-lt:   #E8F4FB;
            --viygo-blue-mid:  #C5E1F0;
        }

        body { font-family: 'DM Sans', sans-serif; }
        h1, h2, h3, h4 { font-family: 'DM Serif Display', serif; }

        .viygo-logo-wrap { position: relative; width: 110px; height: 38px; display: block; }
        .viygo-logo-wrap img {
            position: absolute; top: 0; left: 0;
            height: 38px; width: auto; max-width: 110px;
            object-fit: contain;
            transition: opacity 0.8s ease;
        }
        .viygo-logo-wrap img.logo-hidden  { opacity: 0; }
        .viygo-logo-wrap img.logo-visible { opacity: 1; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: var(--viygo-blue-mid); border-radius: 99px; }

        .cat-nav-link { position: relative; padding-bottom: 14px; }
        .cat-nav-link::after {
            content: ''; position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px; background: var(--viygo-navy);
            transform: scaleX(0); transition: transform 0.2s;
        }
        .cat-nav-link:hover::after,
        .cat-nav-link.active::after { transform: scaleX(1); }

        /* Leaflet stays below sticky navbar */
        .leaflet-container { z-index: 0; }
        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom { z-index: 1; }
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

    {{-- Leaflet JS (must load before per-page map init scripts) --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    {{ $scripts ?? '' }}
</body>
</html>

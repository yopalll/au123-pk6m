<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>{{ $title ?? 'VIYGO' }} — Library Salon Indonesia</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

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

        /* Logo cross-fade container */
        .viygo-logo-wrap { position: relative; width: 110px; height: 38px; display: block; }
        .viygo-logo-wrap img {
            position: absolute; top: 0; left: 0;
            height: 38px; width: auto; max-width: 110px;
            object-fit: contain;
            transition: opacity 0.8s ease;
        }
        .viygo-logo-wrap img.logo-hidden { opacity: 0; }
        .viygo-logo-wrap img.logo-visible { opacity: 1; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: var(--viygo-blue-mid); border-radius: 99px; }

        /* Category nav active underline */
        .cat-nav-link { position: relative; padding-bottom: 14px; }
        .cat-nav-link::after {
            content: ''; position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px; background: var(--viygo-navy);
            transform: scaleX(0); transition: transform 0.2s;
        }
        .cat-nav-link:hover::after,
        .cat-nav-link.active::after { transform: scaleX(1); }
    </style>

    {{ $head ?? '' }}
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- Navbar --}}
    <x-viygo-navbar />

    {{-- Main Content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <x-viygo-footer />

    {{ $scripts ?? '' }}
</body>
</html>

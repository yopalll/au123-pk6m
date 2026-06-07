<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        {{-- Force DARK appearance on auth pages (Serene Floral Noir). --}}
        <script>
        (function () {
            var _get = Storage.prototype.getItem;
            Storage.prototype.getItem = function (key) {
                if (key === 'flux.appearance') return 'dark';
                return _get.apply(this, arguments);
            };
            document.documentElement.classList.add('dark');
        })();
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <style>
            body { font-family: 'Manrope', sans-serif; background: #111316; color: #e2e2e6; }
            .viygo-serif { font-family: 'Playfair Display', serif; }
        </style>
    </head>
    <body class="min-h-screen antialiased" style="background:#111316;">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">

            {{-- Brand panel (desktop) — floral noir --}}
            <div class="relative hidden h-full flex-col p-12 text-white lg:flex overflow-hidden">
                <div class="absolute inset-0" style="background:linear-gradient(rgba(17,19,22,0.55),rgba(17,19,22,0.85)),url('{{ asset('images/floral-noir.png') }}');background-size:cover;background-position:center;"></div>

                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3" wire:navigate>
                    <span class="viygo-serif text-3xl tracking-tight text-[#ffdbc8]">VIYGO</span>
                </a>

                <div class="relative z-20 mt-auto">
                    <div class="text-[11px] font-semibold uppercase tracking-[0.2em] mb-4 text-[#ffdbc8]">
                        ✦ Beauty &amp; Skincare Marketplace
                    </div>
                    <h2 class="viygo-serif text-4xl leading-tight mb-4 text-[#f3ece6]">
                        Your next great<br />
                        <em class="text-[#ffdbc8]">beauty moment</em><br />
                        starts here.
                    </h2>
                    <p class="text-white/50 text-sm leading-relaxed max-w-sm">
                        Booking treatment salon & belanja skincare premium — semua dalam satu tempat yang tenang dan mewah.
                    </p>
                    <div class="mt-8 flex items-center gap-6 text-white/40 text-xs">
                        <div class="flex items-center gap-1.5"><span class="text-[#ffb68b]">★</span><span>4.8 rating rata-rata</span></div>
                        <div class="flex items-center gap-1.5"><span class="text-[#a5cbea]">●</span><span>1.700+ kota</span></div>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="w-full lg:p-8" style="background:#0c0e11;">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[380px] min-h-dvh lg:min-h-0">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 lg:hidden" wire:navigate>
                        <span class="viygo-serif text-2xl text-[#ffdbc8]">VIYGO</span>
                    </a>
                    {{ $slot }}
                    <p class="text-center text-xs text-white/30">
                        © {{ date('Y') }} VIYGO. <a href="{{ route('home') }}" class="hover:text-[#ffdbc8] underline">Kembali ke beranda</a>.
                    </p>
                </div>
            </div>
        </div>
        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

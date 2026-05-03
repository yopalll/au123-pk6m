<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <style>
            :root {
                --viygo-navy:    #1B2D6B;
                --viygo-blue:    #4BA3CC;
                --viygo-blue-lt: #E8F4FB;
            }
            body { font-family: 'DM Sans', sans-serif; }
            .viygo-serif { font-family: 'DM Serif Display', serif; }
        </style>
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">

            {{-- Brand panel (desktop only) --}}
            <div class="relative hidden h-full flex-col p-10 text-white lg:flex" style="background: linear-gradient(135deg, var(--viygo-navy) 0%, #142055 60%, #0F1D4A 100%);">
                {{-- decorative grid --}}
                <div class="absolute inset-0 opacity-[0.06]"
                     style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:48px 48px"></div>
                {{-- soft glow --}}
                <div class="absolute -right-20 top-1/3 w-96 h-96 rounded-full"
                     style="background: radial-gradient(circle, rgba(75,163,204,0.35) 0%, transparent 70%);"></div>

                <a href="{{ route('home') }}" class="relative z-20 flex items-center gap-3 text-lg font-medium" wire:navigate>
                    <img src="{{ asset('icon.png') }}" alt="VIYGO" class="h-10 w-10 rounded-lg shadow-md" onerror="this.style.display='none'" />
                    <span class="viygo-serif text-2xl">VIYGO</span>
                </a>

                <div class="relative z-20 mt-auto">
                    <div class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--viygo-blue);">
                        ✦ Beauty &amp; Wellness Marketplace
                    </div>
                    <h2 class="viygo-serif text-4xl leading-tight mb-4">
                        Your next great<br />
                        <em style="color: var(--viygo-blue);">beauty moment</em><br />
                        starts here.
                    </h2>
                    <p class="text-white/60 text-sm leading-relaxed max-w-sm">
                        Join thousands of customers booking treatments at 8,750+ professional salons across the United Kingdom — all on VIYGO.
                    </p>

                    <div class="mt-8 flex items-center gap-6 text-white/40 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="text-amber-400">★</span>
                            <span>4.8 average rating</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span style="color: var(--viygo-blue);">●</span>
                            <span>1,700+ cities covered</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[380px]">
                    {{-- mobile-only logo --}}
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <img src="{{ asset('icon.png') }}" alt="VIYGO" class="h-12 w-12 rounded-lg" onerror="this.style.display='none'" />
                        <span class="viygo-serif text-2xl" style="color: var(--viygo-navy);">VIYGO</span>
                    </a>
                    {{ $slot }}
                    <p class="text-center text-xs text-zinc-400">
                        © {{ date('Y') }} VIYGO. <a href="{{ route('home') }}" class="hover:text-[#1B2D6B] underline">Back to home</a>.
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

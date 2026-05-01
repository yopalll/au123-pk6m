{{--
    Component: VIYGO navbar (Treatwell-style 2-row)
    Row 1: Logo · search · account
    Row 2: top-level beauty category links (search-based, since the seeded
           Treatwell category slugs are very granular).
--}}

@php
    $topCategories = [
        ['q' => 'hair',    'label' => 'Hair'],
        ['q' => 'facial',  'label' => 'Face'],
        ['q' => 'massage', 'label' => 'Massage'],
        ['q' => 'nail',    'label' => 'Nails'],
        ['q' => 'brow',    'label' => 'Brows & Lashes'],
        ['q' => 'body',    'label' => 'Body'],
        ['q' => 'men',     'label' => "Men's"],
    ];

    $currentQ = request('q', '');
@endphp

<header class="sticky top-0 z-50 bg-white shadow-sm">

    {{-- Row 1: Logo · Search · Account --}}
    <div class="flex items-center gap-4 px-6 h-14 border-b border-gray-100">

        <x-viygo-logo />

        <form action="{{ route('cari') }}" method="GET"
              class="flex-1 max-w-xl flex items-center bg-gray-50 border border-gray-200 rounded-full overflow-hidden
                     focus-within:border-[#4BA3CC] focus-within:ring-2 focus-within:ring-[#4BA3CC]/20 transition-all">
            <svg class="w-4 h-4 ml-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input name="q"
                   value="{{ request('q') }}"
                   placeholder="Search treatments, salons or locations…"
                   class="flex-1 bg-transparent px-3 py-2 text-sm outline-none text-gray-800 placeholder-gray-400" />
            <input name="lokasi"
                   value="{{ request('lokasi') }}"
                   placeholder="Location"
                   class="w-32 bg-transparent border-l border-gray-200 px-3 py-2 text-sm outline-none text-gray-800 placeholder-gray-400" />
            <button type="submit"
                    class="m-1 px-4 py-1.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Search
            </button>
        </form>

        <div class="flex items-center gap-3 ml-auto">
            @guest
                <a href="{{ route('login') }}"
                   class="text-sm font-medium text-gray-700 hover:text-[#1B2D6B] transition-colors">
                    Sign in
                </a>
                <a href="{{ route('register') }}"
                   class="px-4 py-1.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                    Sign up
                </a>
            @else
                <a href="{{ route('akun.index') }}"
                   class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#1B2D6B] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    <span class="hidden lg:inline">My Account</span>
                </a>
            @endguest
        </div>
    </div>

    {{-- Row 2: top category nav --}}
    <div class="px-6 flex items-center gap-1 overflow-x-auto scrollbar-hide border-b border-gray-100">
        @foreach ($topCategories as $cat)
            <a href="{{ route('cari', ['q' => $cat['q']]) }}"
               class="cat-nav-link flex-shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500
                      hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                      {{ str_contains(strtolower($currentQ), $cat['q']) ? 'active text-[#1B2D6B]' : '' }}">
                {{ $cat['label'] }}
            </a>
        @endforeach

        <div class="mx-2 h-4 w-px bg-gray-200 flex-shrink-0"></div>

        <a href="{{ route('gift-card') }}"
           class="cat-nav-link flex-shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->routeIs('gift-card') ? 'active text-[#1B2D6B]' : '' }}">
            Gift Card
        </a>
        <a href="{{ route('lookbook') }}"
           class="cat-nav-link flex-shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->routeIs('lookbook') ? 'active text-[#1B2D6B]' : '' }}">
            Lookbook
        </a>
        <a href="{{ route('treatment-files') }}"
           class="cat-nav-link flex-shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->routeIs('treatment-files') ? 'active text-[#1B2D6B]' : '' }}">
            Treatment Files
        </a>
        <a href="{{ route('mitra') }}"
           class="cat-nav-link flex-shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->routeIs('mitra') ? 'active text-[#1B2D6B]' : '' }}">
            For Salons
        </a>
    </div>

</header>

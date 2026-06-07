{{--
    Component: VIYGO navbar Part 3 (Treatwell-style 2-row + hover dropdowns)
    Row 1: Logo · search · account
    Row 2: 7 kategori utama (Hair / Hair Removal / Massage / Nails / Face / Body / Men's)
           dropdown isinya 6 sub_kategori per kategori dari pivot kategori_sub_kategori,
           urutan dari pivot.urutan. Plus link "See all X treatments" + (untuk Mens) "Barbers".

    Data DB:
      Kategori::active()->with('subKategori')->orderBy('urutan')->get()
--}}

@php
    use App\Models\Kategori;
    use Illuminate\Support\Facades\Cache;

    // OPT-01: Cache navbar categories for 24h to avoid repeat DB hits on every
    // public page render. Use toArray() to serialize as plain arrays, preventing
    // Eloquent Collection __PHP_Incomplete_Class unserialize failures on Windows.
    // Cache is keyed 'nav_kategori'; invalidate in KategoriObserver / KategoriSeeder on save.
    $navKategoriRaw = Cache::remember(
        'nav_kategori',
        now()->addHours(24),
        fn () => Kategori::active()
            ->with(['subKategori' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('urutan')
            ->get()
            ->toArray()   // Serialize as array to avoid unserialize failures
    );
    // Hydrate back into simple objects for template compatibility
    $navKategori = collect($navKategoriRaw)->map(function ($k) {
        $obj = (object) $k;
        $obj->subKategori = collect($k['sub_kategori'] ?? [])->map(fn ($s) => (object) $s);
        return $obj;
    });

    $currentQ = strtolower((string) request('q', ''));
@endphp

<header class="sticky top-0 z-50 bg-white shadow-sm" x-data="{ openCat: null }">

    {{-- Row 1: Logo · Search · Account --}}
    <div class="flex items-center gap-4 px-6 h-14 border-b border-gray-100">

        <x-viygo-logo />

        <form action="{{ route('cari') }}" method="GET"
              class="flex-1 max-w-xl flex items-center bg-gray-50 border border-gray-200 rounded-full overflow-hidden
                     focus-within:border-[#4BA3CC] focus-within:ring-2 focus-within:ring-[#4BA3CC]/20 transition-all">
            <svg class="w-4 h-4 ml-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                @php
                    $role = auth()->user()->role ?? 'customer';
                    $accountTarget = match ($role) {
                        'admin'       => '/admin',
                        'salon_owner' => '/owner',
                        default       => route('akun.index'),
                    };
                    $accountLabel = match ($role) {
                        'admin'       => 'Admin Panel',
                        'salon_owner' => 'Salon Dashboard',
                        default       => 'My Account',
                    };
                @endphp
                {{-- V2: Cart icon dengan badge jumlah --}}
                @php $cartCount = (int) auth()->user()->cartItems()->sum('qty'); @endphp
                <a href="{{ route('shop.cart') }}"
                   class="relative text-gray-700 hover:text-[#1B2D6B] transition-colors" aria-label="Keranjang">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"/>
                    </svg>
                    <span id="nav-cart-count"
                          class="absolute -top-1.5 -right-1.5 bg-[#1B2D6B] text-white text-[10px] font-bold rounded-full min-w-4 h-4 px-1 flex items-center justify-center {{ $cartCount > 0 ? '' : 'hidden' }}">
                        {{ $cartCount > 99 ? '99+' : $cartCount }}
                    </span>
                </a>

                <a href="{{ $accountTarget }}"
                   class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-[#1B2D6B] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    <span class="hidden lg:inline">{{ $accountLabel }}</span>
                </a>
            @endguest
        </div>
    </div>

    {{-- Row 2: top category nav with dropdowns --}}
    <div class="px-6 flex items-center gap-1 overflow-x-auto md:overflow-visible scrollbar-hide">

        @foreach ($navKategori as $kategori)
            @php
                $key      = $kategori->slug;
                $isMens   = $kategori->slug === 'mens';
                $seeLbl   = 'See all ' . strtolower($kategori->name) . ' treatments';
            @endphp
            <div class="relative shrink-0"
                 @mouseenter="openCat = '{{ $key }}'"
                 @mouseleave="openCat = null">

                <a href="{{ route('kategori.show', $kategori->slug) }}"
                   class="cat-nav-link block px-3 text-xs font-semibold uppercase tracking-wider text-gray-500
                          hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                          {{ str_contains($currentQ, strtolower($kategori->name)) ? 'active text-[#1B2D6B]' : '' }}"
                   :class="openCat === '{{ $key }}' ? 'text-[#1B2D6B]' : ''">
                    {{ strtoupper($kategori->name) }}
                </a>

                {{-- Dropdown panel --}}
                <div x-show="openCat === '{{ $key }}'"
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="absolute left-0 top-full w-115 bg-white border-t border-gray-100
                            shadow-2xl rounded-b-xl z-40 overflow-hidden">

                    <div class="grid grid-cols-5 gap-0">

                        {{-- Left: 6 sub_kategori + special links --}}
                        <div class="col-span-3 p-6">
                            <ul class="space-y-1">
                                @foreach ($kategori->subKategori as $sub)
                                    <li>
                                        <a href="{{ route('sub-kategori.show', $sub->slug) }}"
                                           class="block py-1.5 px-2 -mx-2 rounded-md text-sm text-gray-700
                                                  hover:bg-[#E8F4FB] hover:text-[#1B2D6B] transition-colors">
                                            {{ $sub->name }}
                                        </a>
                                    </li>
                                @endforeach

                                @if ($isMens)
                                    {{-- "Barbers" handled by query, bukan row sub_kategori --}}
                                    <li>
                                        <a href="{{ route('kategori.show', 'mens') }}?filter=barbers"
                                           class="block py-1.5 px-2 -mx-2 rounded-md text-sm text-gray-700
                                                  hover:bg-[#E8F4FB] hover:text-[#1B2D6B] transition-colors">
                                            Barbers
                                        </a>
                                    </li>
                                @endif
                            </ul>

                            @unless ($isMens)
                                {{-- "See all X treatments" handled by route /kategori/{slug}, bukan row sub_kategori --}}
                                <div class="mt-4 pt-3 border-t border-gray-100">
                                    <a href="{{ route('kategori.show', $kategori->slug) }}"
                                       class="inline-flex items-center gap-1 text-sm font-bold text-[#1B2D6B] hover:underline">
                                        {{ $seeLbl }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            @endunless
                        </div>

                        {{-- Right: decorative panel — foto background floral noir (ganti gradient biru V1) --}}
                        <div class="col-span-2 hidden md:flex items-center justify-center p-6 relative overflow-hidden"
                             style="background-image: linear-gradient(135deg, rgba(17,19,22,0.45), rgba(17,19,22,0.78)), url('{{ asset('images/floral-noir.png') }}'); background-size: cover; background-position: center;">
                            <span class="font-serif text-7xl select-none leading-none" style="color: rgba(255,182,139,0.40);">
                                {{ mb_substr($kategori->name, 0, 1) }}
                            </span>
                            <span class="absolute bottom-4 right-5 text-[10px] uppercase tracking-widest font-semibold" style="color: rgba(255,219,200,0.65);">
                                {{ $kategori->name }}
                            </span>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach

        <div class="mx-2 h-4 w-px bg-gray-200 shrink-0"></div>

        <a href="{{ route('mitra') }}"
           class="cat-nav-link shrink-0 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->routeIs('mitra') ? 'active text-[#1B2D6B]' : '' }}">
            For Salons
        </a>

        <div class="mx-2 h-4 w-px bg-gray-200 shrink-0"></div>

        {{-- V2: Shop · Lookbook · Komunitas · Empty Return --}}
        <a href="{{ route('shop.index') }}"
           class="cat-nav-link shrink-0 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->is('shop*') ? 'active text-[#1B2D6B]' : '' }}">
            Shop
        </a>
        <a href="{{ route('lookbook.index') }}"
           class="cat-nav-link shrink-0 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->is('lookbook*') ? 'active text-[#1B2D6B]' : '' }}">
            Lookbook
        </a>
        <a href="{{ route('komunitas.index') }}"
           class="cat-nav-link shrink-0 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->is('komunitas*') ? 'active text-[#1B2D6B]' : '' }}">
            Komunitas
        </a>
        <a href="{{ route('emptyReturn.index') }}"
           class="cat-nav-link shrink-0 px-3 text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 hover:text-[#1B2D6B] transition-colors whitespace-nowrap
                  {{ request()->is('empty-return*') ? 'active text-[#1B2D6B]' : '' }}">
            Empty Return
        </a>
    </div>

</header>

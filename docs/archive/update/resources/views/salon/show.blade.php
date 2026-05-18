<x-layouts.public :title="$salon->nama_salon">

<div class="max-w-5xl mx-auto px-6 py-8">

    {{-- ── Breadcrumb ─────────────────────────────────────────────────── --}}
    <nav class="text-xs text-gray-400 mb-4 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-[#4BA3CC]">Beranda</a>
        <span>/</span>
        <a href="{{ route('kategori.show', $salon->services->first()?->kategori?->slug ?? 'rambut') }}" class="hover:text-[#4BA3CC]">
            {{ $salon->services->first()?->kategori?->name ?? 'Kategori' }}
        </a>
        <span>/</span>
        <span class="text-gray-600">{{ $salon->nama_salon }}</span>
    </nav>

    {{-- ── Judul + CTA ────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
        <div>
            <h1 class="text-3xl text-gray-900 mb-2" style="font-family:'DM Serif Display',serif">
                {{ $salon->nama_salon }}
            </h1>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="flex items-center gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= round($salon->rating ?? 4.5) ? 'text-amber-400' : 'text-gray-200' }}"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                    <span class="font-semibold text-gray-800 ml-1">{{ number_format($salon->rating ?? 4.5, 1) }}</span>
                    <a href="#reviews" class="text-[#4BA3CC] text-sm ml-1 hover:underline">{{ number_format($salon->total_review ?? 0) }} ulasan</a>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="#reviews" class="px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors">
                Ulasan
            </a>
            <a href="#about" class="px-4 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-[#1B2D6B] transition-colors">
                Tentang
            </a>
            <a href="{{ route('booking.create', $salon->slug ?? $salon->id_salon) }}"
               class="px-6 py-2 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold hover:bg-[#4BA3CC] transition-colors">
                Booking Sekarang
            </a>
        </div>
    </div>

    {{-- ── Photo Gallery ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-2 rounded-2xl overflow-hidden mb-8 h-64"
         x-data="{ active: 0 }">
        <div class="col-span-2 bg-[#E8F4FB] overflow-hidden">
            @if ($salon->images->isNotEmpty())
                <img src="{{ $salon->images->first()->url }}" alt="{{ $salon->nama_salon }}"
                     class="w-full h-full object-cover" />
            @else
                <div class="w-full h-full flex items-center justify-center text-6xl">✂️</div>
            @endif
        </div>
        <div class="grid grid-rows-2 gap-2">
            @foreach ($salon->images->skip(1)->take(2) as $img)
                <div class="bg-[#E8F4FB] overflow-hidden">
                    <img src="{{ $img->url }}" class="w-full h-full object-cover" />
                </div>
            @endforeach
            @for ($i = $salon->images->skip(1)->take(2)->count(); $i < 2; $i++)
                <div class="bg-[#E8F4FB] flex items-center justify-center text-3xl text-gray-300">📷</div>
            @endfor
        </div>
    </div>

    {{-- ── Info + Services + Reviews ─────────────────────────────────── --}}
    <div class="flex gap-8">

        {{-- Left Column --}}
        <div class="flex-1 min-w-0">

            {{-- Info --}}
            <div class="flex flex-col gap-2 mb-6 text-sm text-gray-600" id="about">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#4BA3CC] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/>
                    </svg>
                    <span>{{ $salon->alamat }}, {{ $salon->kota?->nama }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#4BA3CC] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>Buka: {{ \Carbon\Carbon::parse($salon->opening_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($salon->closing_time)->format('H:i') }}</span>
                </div>
                @if ($salon->phone_number)
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#4BA3CC] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.63A2 2 0 012 .82h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.91a16 16 0 006.72 6.72l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <a href="tel:{{ $salon->phone_number }}" class="text-[#4BA3CC] hover:underline">{{ $salon->phone_number }}</a>
                    </div>
                @endif
            </div>

            @if ($salon->deskripsi)
                <p class="text-sm text-gray-600 leading-relaxed mb-6">{{ $salon->deskripsi }}</p>
            @endif

            {{-- Services by category --}}
            <div class="mb-8">
                <h2 class="text-xl text-[#1B2D6B] mb-4">Layanan</h2>
                @php $servicesByKat = $salon->services->where('status','active')->groupBy('id_kategori'); @endphp
                @foreach ($servicesByKat as $katId => $services)
                    <div class="mb-4" x-data="{ open: true }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between py-3 border-b border-gray-100 text-left">
                            <span class="font-semibold text-gray-800">{{ $services->first()->kategori?->name ?? 'Layanan' }}</span>
                            <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-gray-400 transition-transform"
                                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" class="divide-y divide-gray-50">
                            @foreach ($services as $svc)
                                <div class="flex items-center justify-between py-3.5 px-2">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $svc->nama }}</div>
                                        @if ($svc->deskripsi)
                                            <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($svc->deskripsi, 60) }}</div>
                                        @endif
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $svc->durasi }} menit</div>
                                    </div>
                                    <div class="flex items-center gap-4 flex-shrink-0">
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-900">Rp {{ number_format($svc->harga, 0, ',', '.') }}</div>
                                        </div>
                                        <a href="{{ route('booking.create', ['slug' => $salon->slug ?? $salon->id_salon, 'service' => $svc->id_service]) }}"
                                           class="px-4 py-1.5 border-2 border-[#1B2D6B] text-[#1B2D6B] text-xs font-semibold rounded-full hover:bg-[#1B2D6B] hover:text-white transition-colors">
                                            Pilih
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Reviews --}}
            <div id="reviews">
                <h2 class="text-xl text-[#1B2D6B] mb-4">Ulasan Pelanggan</h2>

                {{-- Rating breakdown --}}
                <div class="flex items-center gap-6 p-4 bg-[#E8F4FB] rounded-xl border border-[#C5E1F0] mb-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-[#1B2D6B]">{{ number_format($salon->rating ?? 4.5, 1) }}</div>
                        <div class="flex justify-center gap-0.5 my-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <div class="text-xs text-gray-500">{{ number_format($salon->total_review ?? 0) }} ulasan</div>
                    </div>
                    <div class="flex-1">
                        @foreach ([5,4,3,2,1] as $star)
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-gray-500 w-3">{{ $star }}</span>
                                <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                    <div class="bg-amber-400 h-1.5 rounded-full" style="width: {{ $star === 5 ? 72 : ($star === 4 ? 18 : ($star === 3 ? 6 : 4)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Review list --}}
                <div class="divide-y divide-gray-100">
                    @forelse ($salon->reviews->take(5) as $review)
                        <div class="py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center text-sm font-bold">
                                        {{ strtoupper(substr($review->user?->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">{{ $review->user?->name ?? 'Anonim' }}</div>
                                        <div class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3 h-3 {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-200' }}"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $review->komentar }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-4">Belum ada ulasan untuk salon ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Right: Sticky Booking Widget --}}
        <div class="hidden lg:block w-72 flex-shrink-0">
            <div class="sticky top-[160px] border border-gray-200 rounded-2xl p-6 shadow-md">
                <h3 class="font-semibold text-gray-900 mb-4">Pilih Layanan</h3>
                <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
                    @foreach ($salon->services->where('status','active')->take(6) as $svc)
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#4BA3CC] hover:bg-[#E8F4FB] cursor-pointer transition-all">
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ $svc->nama }}</div>
                                <div class="text-xs text-gray-400">{{ $svc->durasi }} min</div>
                            </div>
                            <div class="text-sm font-semibold text-[#1B2D6B]">Rp {{ number_format($svc->harga, 0, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('booking.create', $salon->slug ?? $salon->id_salon) }}"
                   class="block w-full py-3 bg-[#1B2D6B] text-white text-center font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                    Booking Sekarang
                </a>
            </div>
        </div>

    </div>
</div>

</x-layouts.public>

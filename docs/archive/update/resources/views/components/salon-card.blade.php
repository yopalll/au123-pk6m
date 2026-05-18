{{--
    Komponen: Salon Card (untuk halaman library/kategori)
    Props:
      $salon       — App\Models\Salon instance
      $layout      — 'list' (default, Treatwell-style) | 'grid'
--}}

@props(['salon', 'layout' => 'list'])

@php
    $primaryImage = $salon->primaryImage?->url ?? $salon->image_url;
    $rating       = number_format($salon->rating ?? 4.5, 1);
    $reviews      = $salon->total_review ?? 0;
    $services     = $salon->services->where('status','active')->take(3);
@endphp

@if ($layout === 'list')
{{-- ── List Layout (Treatwell style) ──────────────────────────────────── --}}
<div class="flex gap-4 py-5 border-b border-gray-100 last:border-0 group">

    {{-- Foto salon --}}
    <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
       class="relative flex-shrink-0 w-48 h-36 rounded-xl overflow-hidden bg-[#E8F4FB]">
        @if ($primaryImage)
            <img src="{{ $primaryImage }}" alt="{{ $salon->nama_salon }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        @else
            <div class="w-full h-full flex items-center justify-center text-4xl">✂️</div>
        @endif
        @if ($salon->is_last_minute ?? false)
            <span class="absolute bottom-2 left-2 bg-[#4BA3CC] text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                Last Minute
            </span>
        @endif
    </a>

    {{-- Info --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2 mb-1">
            <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
               class="text-lg font-semibold text-gray-900 hover:text-[#1B2D6B] transition-colors leading-snug"
               style="font-family:'DM Serif Display',serif">
                {{ $salon->nama_salon }}
            </a>
            <button class="flex-shrink-0 text-gray-300 hover:text-red-400 transition-colors mt-0.5" aria-label="Favorit">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </button>
        </div>

        {{-- Rating --}}
        <div class="flex items-center gap-2 mb-1">
            <div class="flex items-center gap-1">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="w-3.5 h-3.5 {{ $i <= round($salon->rating ?? 4.5) ? 'text-amber-400' : 'text-gray-200' }}"
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <span class="text-sm font-semibold text-gray-800">{{ $rating }}</span>
            <span class="text-sm text-gray-400">{{ number_format($reviews) }} ulasan</span>
        </div>

        {{-- Lokasi --}}
        <div class="flex items-center gap-1 text-sm text-[#4BA3CC] mb-3">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
            </svg>
            {{ $salon->kota?->nama ?? 'Indonesia' }}
        </div>

        {{-- Services --}}
        <div class="space-y-1.5">
            @forelse ($services as $svc)
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <span class="text-gray-700">{{ $svc->nama }}</span>
                        <span class="text-gray-400 ml-2">{{ $svc->durasi }} min</span>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-800 font-medium">mulai Rp {{ number_format($svc->harga, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <span class="text-sm text-gray-400">Belum ada layanan terdaftar</span>
            @endforelse
        </div>

        {{-- Quick view toggle --}}
        <button x-data="{ open: false }"
                @click="open = !open"
                class="mt-3 flex items-center gap-1 text-sm text-[#1B2D6B] font-medium hover:underline">
            <span x-text="open ? 'Sembunyikan detail' : 'Lihat detail salon'"></span>
            <svg :class="open ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform"
                 fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </button>
    </div>
</div>

@else
{{-- ── Grid Layout ──────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
    <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}">
        <div class="h-44 bg-[#E8F4FB] overflow-hidden relative">
            @if ($primaryImage)
                <img src="{{ $primaryImage }}" alt="{{ $salon->nama_salon }}"
                     class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
            @else
                <div class="w-full h-full flex items-center justify-center text-5xl">✂️</div>
            @endif
            @if ($salon->badge ?? false)
                <span class="absolute top-3 left-3 bg-[#1B2D6B] text-white text-xs font-semibold px-2.5 py-1 rounded-full">
                    {{ $salon->badge }}
                </span>
            @endif
        </div>
    </a>
    <div class="p-4">
        <div class="font-semibold text-gray-900 mb-1 text-base" style="font-family:'DM Serif Display',serif">
            {{ $salon->nama_salon }}
        </div>
        <div class="flex items-center gap-1 text-sm mb-2">
            <span class="text-amber-400">★</span>
            <span class="font-semibold">{{ $rating }}</span>
            <span class="text-gray-400">({{ $reviews }})</span>
            <span class="text-gray-300 mx-1">·</span>
            <span class="text-[#4BA3CC] text-xs">{{ $salon->kota?->nama ?? '' }}</span>
        </div>
        <div class="flex items-center justify-between mt-3">
            <span class="text-xs text-gray-500">mulai <strong class="text-gray-800 text-sm">Rp {{ number_format($services->min('harga') ?? 0, 0, ',', '.') }}</strong></span>
            <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
               class="px-3 py-1.5 bg-[#1B2D6B] text-white text-xs font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Booking
            </a>
        </div>
    </div>
</div>
@endif

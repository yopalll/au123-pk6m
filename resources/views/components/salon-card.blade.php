{{--
    Component: Salon Card
    Props:
      $salon  — App\Models\Salon
      $layout — 'list' (default, Treatwell-style) | 'grid'
--}}

@props(['salon', 'layout' => 'list'])

@php
    $primaryImage = ($salon->primaryImage?->image_url) ?? ($salon->image_url ?? null);
    $rating       = number_format($salon->rating ?? 4.5, 1);
    $reviews      = $salon->total_review ?? 0;
    $services     = $salon->services->where('status', 'active')->take(3);
    $location     = $salon->kota?->nama;
    $minPrice     = $services->min('harga');
    // Tag pivot: sub_kategori yg disediakan salon (max 3 chip biar muat)
    $subKategoris = $salon->relationLoaded('subKategoris')
        ? $salon->subKategoris->take(3)
        : collect();
    $isFavourite  = auth()->check()
        && (auth()->user()->role === 'customer')
        && auth()->user()->hasFavourited($salon->id_salon);
@endphp

@if ($layout === 'list')
{{-- ── List Layout ──────────────────────────────────────────────────── --}}
<div class="flex gap-4 py-5 border-b border-gray-100 last:border-0 group">

    <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
       class="relative shrink-0 w-48 h-36 rounded-xl overflow-hidden bg-[#E8F4FB]">
        @if ($primaryImage)
            <img src="{{ $primaryImage }}" alt="{{ $salon->nama_salon }}"
                 loading="lazy"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        @else
            <div class="w-full h-full flex items-center justify-center text-4xl">✂️</div>
        @endif
    </a>

    <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2 mb-1">
            <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
               class="text-lg font-semibold text-gray-900 hover:text-[#1B2D6B] transition-colors leading-snug"
               style="font-family:'DM Serif Display',serif">
                {{ $salon->nama_salon }}
            </a>
            @if (auth()->check() && auth()->user()->role === 'customer')
                <form action="{{ route('akun.favorit.toggle', $salon->id_salon) }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="text-{{ $isFavourite ? 'red-500' : 'gray-300' }} hover:text-red-500 transition-colors mt-0.5"
                            aria-label="{{ $isFavourite ? 'Remove from favourites' : 'Save to favourites' }}">
                        <svg class="w-5 h-5" fill="{{ $isFavourite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="shrink-0 text-gray-300 hover:text-red-400 transition-colors mt-0.5" aria-label="Sign in to save favourites">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>
            @endif
        </div>

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
            <span class="text-sm text-gray-400">{{ number_format($reviews) }} reviews</span>
        </div>

        @if ($location)
            <div class="flex items-center gap-1 text-sm text-[#4BA3CC] mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                </svg>
                {{ $location }}
            </div>
        @endif

        @if ($subKategoris->isNotEmpty())
            <div class="flex flex-wrap gap-1.5 mb-3">
                @foreach ($subKategoris as $sk)
                    <a href="{{ route('sub-kategori.show', $sk->slug) }}"
                       class="px-2 py-0.5 text-xs font-medium text-[#1B2D6B] bg-[#E8F4FB] hover:bg-[#4BA3CC] hover:text-white rounded-full transition-colors">
                        {{ $sk->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="space-y-1.5">
            @forelse ($services as $svc)
                <div class="flex items-center justify-between text-sm">
                    <div>
                        <span class="text-gray-700">{{ $svc->nama }}</span>
                        <span class="text-gray-400 ml-2">{{ $svc->durasi }} min</span>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-800 font-medium">from {{ \App\Support\Money::rupiah($svc->harga) }}</span>
                    </div>
                </div>
            @empty
                <span class="text-sm text-gray-400">No services listed yet</span>
            @endforelse
        </div>

        <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
           class="mt-3 inline-flex items-center gap-1 text-sm text-[#1B2D6B] font-medium hover:underline">
            View salon details
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </a>
    </div>
</div>

@else
{{-- ── Grid Layout ──────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition-all duration-200">
    <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}">
        <div class="h-44 bg-[#E8F4FB] overflow-hidden relative">
            @if ($primaryImage)
                <img src="{{ $primaryImage }}" alt="{{ $salon->nama_salon }}"
                     loading="lazy"
                     class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" />
            @else
                <div class="w-full h-full flex items-center justify-center text-5xl">✂️</div>
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
            <span class="text-gray-400">({{ number_format($reviews) }})</span>
            @if ($location)
                <span class="text-gray-300 mx-1">·</span>
                <span class="text-[#4BA3CC] text-xs">{{ $location }}</span>
            @endif
        </div>
        @if ($subKategoris->isNotEmpty())
            <div class="flex flex-wrap gap-1 mb-2">
                @foreach ($subKategoris->take(2) as $sk)
                    <a href="{{ route('sub-kategori.show', $sk->slug) }}"
                       class="px-1.5 py-0.5 text-[10px] font-medium text-[#1B2D6B] bg-[#E8F4FB] hover:bg-[#4BA3CC] hover:text-white rounded-full transition-colors">
                        {{ $sk->name }}
                    </a>
                @endforeach
            </div>
        @endif
        <div class="flex items-center justify-between mt-3">
            <span class="text-xs text-gray-500">from <strong class="text-gray-800 text-sm">{{ \App\Support\Money::rupiah($minPrice ?? 0) }}</strong></span>
            <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}"
               class="px-3 py-1.5 bg-[#1B2D6B] text-white text-xs font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Book
            </a>
        </div>
    </div>
</div>
@endif

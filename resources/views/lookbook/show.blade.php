<x-layouts.public :title="$lookbook->judul">
@php
    $imgUrl = fn ($url) => $url ? (\Illuminate\Support\Str::startsWith($url, ['http','//']) ? $url : asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url)) : 'https://placehold.co/1000x700/1a1c1f/ffb68b?text=Slide';
@endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8"
     x-data="{ current: 0, total: {{ $lookbook->slides->count() }}, openPin: null }">

    <a href="{{ route('lookbook.index') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Semua Lookbook</a>

    <div class="text-center my-6">
        <span class="text-xs uppercase tracking-widest text-[#4BA3CC] font-semibold">{{ $lookbook->tema }}</span>
        <h1 class="text-3xl sm:text-4xl font-bold mt-2" style="font-family:'DM Serif Display',serif">{{ $lookbook->judul }}</h1>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto text-sm">{{ $lookbook->deskripsi }}</p>
    </div>

    @forelse ($lookbook->slides as $i => $slide)
        <div x-show="current === {{ $i }}" x-cloak>
            {{-- Hero image + pins --}}
            <div class="relative rounded-2xl overflow-hidden">
                <img src="{{ $imgUrl($slide->image_url) }}" alt="{{ $slide->judul }}" class="w-full object-cover max-h-[75vh]">
                @foreach ($slide->items as $item)
                    @if ($item->product)
                        <div class="absolute" style="left: {{ $item->position_x }}%; top: {{ $item->position_y }}%;">
                            <button type="button" @click.stop="openPin = (openPin === '{{ $i }}-{{ $loop->index }}' ? null : '{{ $i }}-{{ $loop->index }}')"
                                    class="relative w-6 h-6 -ml-3 -mt-3 bg-white rounded-full shadow-lg flex items-center justify-center text-sm font-bold text-[#1B2D6B]">
                                +
                                <span class="absolute inset-0 rounded-full bg-white animate-ping opacity-40"></span>
                            </button>
                            <div x-show="openPin === '{{ $i }}-{{ $loop->index }}'" x-cloak @click.outside="openPin=null"
                                 class="absolute z-10 bottom-8 left-1/2 -translate-x-1/2 bg-white rounded-xl shadow-2xl p-3 w-44">
                                <img src="{{ $item->product->primaryImage?->image_url ? asset(\Illuminate\Support\Str::startsWith($item->product->primaryImage->image_url,'public/') ? str_replace('public/','storage/',$item->product->primaryImage->image_url) : $item->product->primaryImage->image_url) : 'https://placehold.co/200x200/1a1c1f/ffb68b?text=VIYGO' }}" class="w-full h-24 object-cover rounded-lg mb-2">
                                <p class="text-xs font-medium line-clamp-2">{{ $item->product->nama }}</p>
                                <p class="text-xs text-[#1B2D6B] font-bold mt-0.5">Rp {{ number_format($item->product->harga,0,',','.') }}</p>
                                <p class="text-[10px] mt-1 {{ $item->product->stok > 0 ? 'text-emerald-600' : 'text-red-500' }}">● {{ $item->product->stok > 0 ? 'In Stock' : 'Out of Stock' }}</p>
                                <a href="{{ route('shop.produk.show', $item->product->slug) }}" class="block text-center text-xs mt-2 py-1.5 bg-[#1B2D6B] text-white rounded-full">Lihat Produk</a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Slide content --}}
            <div class="max-w-2xl mx-auto py-6">
                @if ($slide->judul)<h2 class="text-2xl font-semibold mb-2" style="font-family:'DM Serif Display',serif">{{ $slide->judul }}</h2>@endif
                @if ($slide->deskripsi)<p class="text-gray-600 leading-relaxed text-sm">{{ $slide->deskripsi }}</p>@endif
                @if ($slide->tips)
                    <div class="mt-4 bg-amber-50 rounded-xl p-4">
                        <p class="text-sm font-medium text-amber-800 mb-1">💡 Tips</p>
                        <p class="text-sm text-amber-700">{{ $slide->tips }}</p>
                    </div>
                @endif
                @if ($slide->items->count())
                    <div class="mt-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Produk di slide ini</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($slide->items as $item)
                                @if ($item->product)
                                    <a href="{{ route('shop.produk.show', $item->product->slug) }}" class="flex items-center gap-2 bg-gray-50 rounded-xl p-2 hover:bg-gray-100">
                                        <span class="text-xs font-medium line-clamp-1 max-w-[140px]">{{ $item->product->nama }}</span>
                                        <span class="text-xs text-[#1B2D6B]">Rp {{ number_format($item->product->harga,0,',','.') }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-center text-gray-400 py-10">Lookbook ini belum punya slide.</p>
    @endforelse

    {{-- Nav --}}
    @if ($lookbook->slides->count() > 1)
        <div class="flex items-center justify-center gap-4 pb-4">
            <button @click="current = (current - 1 + total) % total" class="w-10 h-10 rounded-full bg-white shadow-md hover:shadow-lg">←</button>
            <div class="flex gap-2">
                @foreach ($lookbook->slides as $i => $s)
                    <button @click="current = {{ $i }}" :class="current === {{ $i }} ? 'bg-[#1B2D6B]' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></button>
                @endforeach
            </div>
            <button @click="current = (current + 1) % total" class="w-10 h-10 rounded-full bg-white shadow-md hover:shadow-lg">→</button>
        </div>
    @endif

    {{-- Shop this look + share --}}
    <div class="sticky bottom-20 md:bottom-6 flex justify-center gap-3 pt-4">
        @auth
            <form method="POST" action="{{ route('lookbook.shopAll', $lookbook->slug) }}">
                @csrf
                <button class="px-7 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full shadow-xl hover:bg-[#4BA3CC] transition-colors">🛍️ Shop This Look</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="px-7 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full shadow-xl">🛍️ Shop This Look</a>
        @endauth
        <a href="https://wa.me/?text={{ urlencode($lookbook->judul.' '.request()->url()) }}" target="_blank"
           class="px-4 py-3 bg-emerald-500 text-white rounded-full shadow-xl text-sm">Share</a>
    </div>

    @if (session('success'))
        <div class="fixed bottom-32 md:bottom-20 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-5 py-2.5 rounded-full text-sm z-[60]" x-data x-init="setTimeout(()=>$el.remove(),3000)">{{ session('success') }}</div>
    @endif
</div>
</x-layouts.public>

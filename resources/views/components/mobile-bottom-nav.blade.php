{{-- Mobile bottom tab bar — hanya tampil < 768px (md) --}}
@php
    $tab = 'flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium flex-1 h-full';
    $active = 'text-[#1B2D6B]';
    $idle = 'text-gray-400';
@endphp

<nav class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-gray-200 md:hidden"
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="flex items-stretch h-14">
        <a href="{{ route('home') }}" class="{{ $tab }} {{ request()->routeIs('home') ? $active : $idle }}">
            <span class="text-lg leading-none">🏠</span>
            <span>Home</span>
        </a>
        <a href="{{ route('shop.index') }}" class="{{ $tab }} {{ request()->is('shop*') ? $active : $idle }}">
            <span class="text-lg leading-none">🧴</span>
            <span>Shop</span>
        </a>
        <a href="{{ route('lookbook.index') }}" class="{{ $tab }} {{ request()->is('lookbook*') ? $active : $idle }}">
            <span class="text-lg leading-none">📸</span>
            <span>Lookbook</span>
        </a>
        <a href="{{ route('komunitas.index') }}" class="{{ $tab }} {{ request()->is('komunitas*') ? $active : $idle }}">
            <span class="text-lg leading-none">💬</span>
            <span>Forum</span>
        </a>
        <a href="{{ auth()->check() ? route('akun.index') : route('login') }}"
           class="{{ $tab }} {{ request()->is('akun*') ? $active : $idle }}">
            <span class="text-lg leading-none">👤</span>
            <span>Akun</span>
        </a>
    </div>
</nav>

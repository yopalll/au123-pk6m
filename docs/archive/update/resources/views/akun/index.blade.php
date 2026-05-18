<x-layouts.public title="Akun Saya">
<div class="max-w-4xl mx-auto px-6 py-10">

    {{-- Profile Header --}}
    <div class="flex items-center justify-between gap-6 mb-8 flex-wrap">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900" style="font-family:'DM Serif Display',serif">
                    {{ auth()->user()->name }}
                </h1>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
            </div>
        </div>

        {{-- VIYGO Rewards --}}
        <div class="flex items-center gap-4 bg-[#E8F4FB] border border-[#C5E1F0] rounded-xl px-6 py-4">
            <div>
                <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-0.5">VIYGO REWARDS</div>
                <div class="text-2xl font-bold text-[#1B2D6B]">0 <span class="text-base font-normal text-gray-400">/ 1500 poin</span></div>
            </div>
            <a href="{{ route('akun.reward') }}"
               class="px-4 py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors whitespace-nowrap">
                Kumpulkan Poin
            </a>
        </div>
    </div>

    {{-- Dashboard Grid --}}
    <div class="grid md:grid-cols-2 gap-4">

        @php
            $items = [
                ['icon'=>'📅', 'title'=>'Booking Saya', 'desc'=>'Lihat dan kelola booking masa lalu & mendatang', 'link'=>route('akun.bookings'), 'link_text'=>'Lihat booking', 'count'=> $upcomingCount ?? 0],
                ['icon'=>'👤', 'title'=>'Data Diri', 'desc'=>'Edit informasi pribadi kamu', 'link'=>route('akun.pengaturan'), 'link_text'=>'Edit profil', 'badge'=>'Baru'],
                ['icon'=>'❤️', 'title'=>'Favorit', 'desc'=>'Salon dan layanan yang kamu simpan', 'link'=>route('akun.favorit'), 'link_text'=>'Lihat favorit'],
                ['icon'=>'🎁', 'title'=>'VIYGO Rewards', 'desc'=>'Kumpulkan poin & dapatkan diskon menarik', 'link'=>route('akun.reward'), 'link_text'=>'Lihat reward'],
                ['icon'=>'💳', 'title'=>'Dompet', 'desc'=>'Kelola voucher dan saldo VIYGO-mu', 'link'=>'#', 'link_text'=>'Kelola dompet'],
                ['icon'=>'👥', 'title'=>'Ajak Teman', 'desc'=>'Kamu dan temanmu dapat Rp 25.000 untuk booking berikutnya', 'link'=>'#', 'link_text'=>'Ajak sekarang'],
            ];
        @endphp

        @foreach ($items as $item)
            <a href="{{ $item['link'] }}"
               class="flex items-start gap-4 p-5 border border-gray-100 rounded-2xl hover:border-[#4BA3CC] hover:bg-[#E8F4FB]/40 transition-all group">
                <div class="w-10 h-10 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-xl flex-shrink-0">
                    {{ $item['icon'] }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-semibold text-gray-900 group-hover:text-[#1B2D6B] transition-colors">{{ $item['title'] }}</span>
                        @if (isset($item['badge']))
                            <span class="px-2 py-0.5 bg-[#4BA3CC] text-white text-xs rounded-full">{{ $item['badge'] }}</span>
                        @endif
                        @if (isset($item['count']) && $item['count'] > 0)
                            <span class="px-2 py-0.5 bg-[#1B2D6B] text-white text-xs rounded-full">{{ $item['count'] }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500">{{ $item['desc'] }}</p>
                    <span class="text-xs text-[#4BA3CC] font-medium mt-1 inline-block group-hover:underline">{{ $item['link_text'] }} →</span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Logout --}}
    <form action="{{ route('logout') }}" method="POST" class="mt-8">
        @csrf
        <button type="submit" class="text-sm text-gray-400 hover:text-red-500 transition-colors">
            Keluar dari akun
        </button>
    </form>
</div>
</x-layouts.public>

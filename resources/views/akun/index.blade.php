<x-layouts.public title="My Account">
<div class="max-w-4xl mx-auto px-6 py-10">

    {{-- Profile Header --}}
    <div class="flex items-center justify-between gap-6 mb-8 flex-wrap">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr(auth()->user()->full_name ?: auth()->user()->email, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900" style="font-family:'DM Serif Display',serif">
                    {{ auth()->user()->full_name ?: 'Welcome' }}
                </h1>
                <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    {{-- Dashboard Grid --}}
    <div class="grid md:grid-cols-2 gap-4">

        @php
            $items = [
                ['icon'=>'📅', 'title'=>'My Bookings',     'desc'=>'View and manage upcoming and past bookings',         'link'=>route('akun.bookings'),    'link_text'=>'View bookings',  'count'=> $upcomingCount ?? 0],
                ['icon'=>'👤', 'title'=>'Personal Info',   'desc'=>'Edit your profile details',                          'link'=>route('akun.pengaturan'),  'link_text'=>'Edit profile'],
                ['icon'=>'❤️', 'title'=>'Favourites',      'desc'=>'Salons and treatments you have saved',               'link'=>route('akun.favorit'),     'link_text'=>'View favourites'],
            ];
        @endphp

        @foreach ($items as $item)
            <a href="{{ $item['link'] }}"
               class="flex items-start gap-4 p-5 border border-gray-100 rounded-2xl hover:border-[#4BA3CC] hover:bg-[#E8F4FB]/40 transition-all group">
                <div class="w-10 h-10 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-xl shrink-0">
                    {{ $item['icon'] }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="font-semibold text-gray-900 group-hover:text-[#1B2D6B] transition-colors">{{ $item['title'] }}</span>
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
            Sign out
        </button>
    </form>
</div>
</x-layouts.public>

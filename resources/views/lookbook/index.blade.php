<x-layouts.public title="Lookbook">

{{-- Hero --}}
<div class="relative bg-[#1B2D6B] py-20 overflow-hidden">
    <div class="absolute inset-0 opacity-25"
         style="background:radial-gradient(circle at 30% 30%, #4BA3CC 0%, transparent 45%),
                          radial-gradient(circle at 70% 70%, #C5E1F0 0%, transparent 35%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Style Inspiration</div>
        <h1 class="text-4xl md:text-5xl text-white mb-4" style="font-family:'DM Serif Display',serif">
            The VIYGO Lookbook
        </h1>
        <p class="text-white/70 text-lg max-w-xl mx-auto mb-6">
            Hand-picked looks from the UK's best salons — and a one-tap link to book the exact treatment.
        </p>
        <div class="flex justify-center gap-2 text-xs text-white/60">
            <span>500+ looks curated weekly</span>
            <span>·</span>
            <span>Updated daily</span>
        </div>
    </div>
</div>

{{-- Category filter --}}
<div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-gray-100" x-data="{ active: 'All' }">
    <div class="max-w-6xl mx-auto px-6 py-4 flex gap-2 overflow-x-auto">
        @foreach(['All','Hair','Nails','Makeup','Brows','Lashes','Skincare','Massage'] as $cat)
            <button type="button"
                    @click="active = '{{ $cat }}'"
                    :class="active === '{{ $cat }}'
                            ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]'
                            : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]'"
                    class="px-5 py-2 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
                {{ $cat }}
            </button>
        @endforeach
    </div>
</div>

{{-- Featured editorial --}}
<div class="max-w-6xl mx-auto px-6 py-12">
    <div class="grid md:grid-cols-2 gap-6">
        <div class="relative aspect-[4/5] md:aspect-auto md:row-span-2 rounded-2xl overflow-hidden bg-gradient-to-br from-[#1B2D6B] to-[#4BA3CC] flex items-end p-6">
            <div class="text-white">
                <span class="text-xs font-bold text-white/70 uppercase tracking-wider">Editor's Pick</span>
                <h2 class="text-3xl mt-2 mb-2" style="font-family:'DM Serif Display',serif">
                    Spring 2026 Hair Edit
                </h2>
                <p class="text-sm text-white/80 mb-4">
                    Soft layers, butter-blonde balayage, and a return to the curtain bang.
                </p>
                <a href="#" class="text-sm font-semibold inline-flex items-center gap-1 hover:gap-2 transition-all">
                    Read the edit →
                </a>
            </div>
        </div>
        @foreach([
            ['💅','The Almond Nail Renaissance','Why short, almond-shaped nails are dominating 2026.'],
            ['💄','Glass Skin in 5 Steps','How to recreate the dewy K-beauty finish at home — or save the time and book a Hydra Facial.'],
        ] as [$emoji, $title, $blurb])
            <div class="bg-gray-50 border border-gray-100 hover:border-[#C5E1F0] rounded-2xl p-6 transition-all cursor-pointer">
                <div class="text-4xl mb-3">{{ $emoji }}</div>
                <h3 class="font-semibold text-gray-900 mb-1">{{ $title }}</h3>
                <p class="text-sm text-gray-500 mb-3">{{ $blurb }}</p>
                <a href="#" class="text-xs text-[#4BA3CC] font-semibold hover:underline">Read more →</a>
            </div>
        @endforeach
    </div>
</div>

{{-- Masonry grid --}}
<div class="max-w-6xl mx-auto px-6 pb-12">
    <div class="flex items-end justify-between mb-6">
        <h2 class="text-2xl text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">
            Trending now
        </h2>
        <a href="{{ route('cari') }}" class="text-sm text-[#4BA3CC] hover:underline">See all salons →</a>
    </div>

    @php
        $looks = [
            ['💇','Soft butter-blonde balayage','Hair','Bond Street Hair Co.','45 min','£85'],
            ['💅','Almond-shaped russet gel','Nails','The Polished Studio','60 min','£35'],
            ['🧖','24-carat gold facial','Skincare','Glow Atelier','75 min','£120'],
            ['🤨','Henna-tinted micro-brow','Brows','Brow Bar London','30 min','£25'],
            ['💆','Aromatherapy back massage','Massage','Calm Living Spa','60 min','£70'],
            ['✂️','Curtain bangs & soft layers','Hair','Hide Salon','45 min','£60'],
            ['👁️','Hybrid lash lift & tint','Lashes','Lash Lab','60 min','£55'],
            ['💋','Velvet matte lip blush','Makeup','Makeup Bar','45 min','£40'],
            ['🪮','Curl-defined silk press','Hair','Curl & Co.','90 min','£95'],
            ['🌿','Detox seaweed wrap','Spa','Calm Living Spa','75 min','£110'],
            ['🎨','Russian volume Russian','Lashes','Lash Lab','120 min','£75'],
            ['💄','Dewy bridal makeup trial','Makeup','Makeup Bar','60 min','£90'],
        ];
    @endphp

    <div class="columns-2 md:columns-3 lg:columns-4 gap-4">
        @foreach ($looks as $i => [$emoji, $title, $cat, $salon, $duration, $price])
            <div class="break-inside-avoid mb-4 rounded-2xl overflow-hidden bg-white border border-gray-100 hover:border-[#C5E1F0] hover:shadow-lg transition-all group cursor-pointer">
                {{-- Aspect varies for visual rhythm --}}
                <div class="{{ ['aspect-square','aspect-[3/4]','aspect-[4/5]','aspect-[5/6]'][$i % 4] }}
                            bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-5xl
                            group-hover:scale-105 transition-transform">
                    {{ $emoji }}
                </div>
                <div class="p-4">
                    <span class="text-[10px] font-bold text-[#4BA3CC] uppercase tracking-wider">{{ $cat }}</span>
                    <h3 class="text-sm font-semibold text-gray-900 mt-1 leading-snug">{{ $title }}</h3>
                    <p class="text-xs text-gray-400 mt-1">{{ $salon }}</p>
                    <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-gray-100">
                        <span class="text-xs text-gray-500">{{ $duration }}</span>
                        <span class="text-sm font-bold text-[#1B2D6B]">{{ $price }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="text-center mt-10">
        <button class="px-8 py-3 border-2 border-[#1B2D6B] text-[#1B2D6B] font-semibold rounded-full hover:bg-[#1B2D6B] hover:text-white transition-colors">
            Load more looks
        </button>
    </div>
</div>

{{-- Featured stylists --}}
<div class="bg-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl text-[#1B2D6B] text-center mb-2" style="font-family:'DM Serif Display',serif">
            Stylists to know
        </h2>
        <p class="text-center text-gray-500 text-sm mb-10">
            The artists behind this season's most-saved looks.
        </p>

        <div class="grid md:grid-cols-4 gap-6">
            @foreach([
                ['Aurelia Ross','Hair colourist','London','💇'],
                ['Mia Tan','Nail artist','Manchester','💅'],
                ['Léa Petit','Skin therapist','Edinburgh','🧖'],
                ['Jordan Okafor','Brow specialist','Birmingham','🤨'],
            ] as [$name, $role, $city, $emoji])
                <div class="bg-white rounded-2xl p-6 text-center border border-gray-100 hover:border-[#C5E1F0] transition-all cursor-pointer">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] mx-auto mb-3 flex items-center justify-center text-3xl">
                        {{ $emoji }}
                    </div>
                    <div class="font-semibold text-gray-900">{{ $name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $role }} · {{ $city }}</div>
                    <a href="#" class="text-xs text-[#4BA3CC] mt-3 inline-block hover:underline">View work →</a>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="max-w-3xl mx-auto px-6 py-16 text-center">
    <h2 class="text-3xl text-[#1B2D6B] mb-3" style="font-family:'DM Serif Display',serif">
        Ready to book your look?
    </h2>
    <p class="text-gray-500 text-sm mb-6 max-w-lg mx-auto">
        Find the salon nearest you and book the exact treatment from the lookbook in under a minute.
    </p>
    <a href="{{ route('cari') }}" class="inline-block px-8 py-3.5 bg-[#1B2D6B] text-white font-bold rounded-full hover:bg-[#4BA3CC] transition-colors">
        Find a salon →
    </a>
</div>

</x-layouts.public>

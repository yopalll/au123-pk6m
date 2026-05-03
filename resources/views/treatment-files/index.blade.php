<x-layouts.public title="Treatment Files">

{{-- Hero --}}
<div class="relative bg-[#1B2D6B] py-20 overflow-hidden">
    <div class="absolute inset-0 opacity-20"
         style="background:radial-gradient(circle at 25% 60%, #4BA3CC 0%, transparent 45%),
                          radial-gradient(circle at 75% 30%, #C5E1F0 0%, transparent 35%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">The VIYGO Journal</div>
        <h1 class="text-4xl md:text-5xl text-white mb-4" style="font-family:'DM Serif Display',serif">
            Treatment Files
        </h1>
        <p class="text-white/70 text-lg max-w-xl mx-auto">
            Expert guides, honest reviews, and treatment trends — written by salon professionals, edited for everyone.
        </p>

        {{-- Inline search --}}
        <div class="mt-8 max-w-md mx-auto">
            <div class="relative">
                <input type="search" placeholder="Search articles…"
                       class="w-full pl-11 pr-4 py-3 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white placeholder:text-white/50 outline-none focus:bg-white/15 focus:border-white/40 transition-colors" />
                <svg class="absolute left-4 top-3.5 w-5 h-5 text-white/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- Category tags --}}
<div class="border-b border-gray-100 bg-white">
    <div class="max-w-6xl mx-auto px-6 py-4 flex gap-2 overflow-x-auto">
        @foreach(['All','Hair','Nails','Skincare','Makeup','Massage','Wellness','Trends'] as $i => $cat)
            <a href="#"
               class="px-4 py-1.5 rounded-full text-sm whitespace-nowrap transition-colors
                      {{ $i === 0 ? 'bg-[#1B2D6B] text-white' : 'border border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }}">
                {{ $cat }}
            </a>
        @endforeach
    </div>
</div>

{{-- Featured story --}}
<div class="max-w-6xl mx-auto px-6 py-12">
    <div class="grid md:grid-cols-3 gap-8">
        {{-- Big feature --}}
        <div class="md:col-span-2 group cursor-pointer">
            <div class="aspect-video rounded-2xl overflow-hidden bg-linear-to-br from-[#1B2D6B] via-[#2C4189] to-[#4BA3CC] mb-5 flex items-center justify-center text-7xl">
                💇
            </div>
            <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Featured · Hair</span>
            <h2 class="text-3xl text-[#1B2D6B] mt-2 mb-3 group-hover:text-[#4BA3CC] transition-colors" style="font-family:'DM Serif Display',serif">
                The Complete Hair Care Guide for 2026
            </h2>
            <p class="text-gray-600 leading-relaxed mb-3">
                From the protein/moisture balance most people overlook, to the bond-builders worth the splurge —
                we asked five professional stylists what they actually do at home.
            </p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span>By Aurelia Ross</span>
                <span>·</span>
                <span>12 min read</span>
                <span>·</span>
                <span>2 May 2026</span>
            </div>
        </div>

        {{-- Side column: secondary features --}}
        <div class="space-y-6">
            @foreach([
                ['💅','Nails','The 5 biggest nail-art trends this season','5 min'],
                ['🧖','Skincare','How to care for your skin after a facial','7 min'],
                ['💆','Massage','The mental health benefits of regular massage','6 min'],
            ] as [$emoji, $cat, $title, $read])
                <div class="group cursor-pointer flex gap-4">
                    <div class="w-24 h-24 rounded-xl bg-linear-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-3xl shrink-0">
                        {{ $emoji }}
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-[#4BA3CC] uppercase tracking-wider">{{ $cat }}</span>
                        <h3 class="text-sm font-semibold text-gray-900 mt-1 leading-snug group-hover:text-[#4BA3CC] transition-colors">
                            {{ $title }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-1">{{ $read }} read</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Latest articles grid --}}
<div class="bg-gray-50 py-16">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex items-end justify-between mb-8">
            <h2 class="text-3xl text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">
                Latest articles
            </h2>
            <a href="#" class="text-sm text-[#4BA3CC] hover:underline">All articles →</a>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['How to pick the right salon for your wedding','Hair','💇','Mia Tan','8 min'],
                ['Gel vs regular manicure: which lasts longer?','Nails','💅','Jordan Okafor','5 min'],
                ['Hair colour trends to try this season','Hair','💇','Aurelia Ross','9 min'],
                ['Hydra-facial vs classic facial: which suits you?','Skincare','🧖','Léa Petit','10 min'],
                ['Tips for brows that stay full and natural','Brows','🤨','Brow Bar','4 min'],
                ['Dewy bridal makeup: the at-home rehearsal','Makeup','💄','Makeup Bar','6 min'],
                ['Why everyone is talking about salt-water hair','Trends','🌊','Aurelia Ross','7 min'],
                ['The science of post-workout massage','Wellness','💆','Calm Living','8 min'],
                ['What to ask your colourist before you sit down','Hair','💇','Hide Salon','5 min'],
            ] as [$title, $cat, $emoji, $author, $read])
                <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 hover:border-[#C5E1F0] hover:shadow-lg transition-all group cursor-pointer">
                    <div class="aspect-[16/10] bg-linear-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-5xl">
                        {{ $emoji }}
                    </div>
                    <div class="p-5">
                        <span class="text-[10px] font-bold text-[#4BA3CC] uppercase tracking-wider">{{ $cat }}</span>
                        <h3 class="text-base font-semibold text-gray-900 mt-1.5 leading-snug group-hover:text-[#4BA3CC] transition-colors">
                            {{ $title }}
                        </h3>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                            <span class="text-xs text-gray-500">{{ $author }}</span>
                            <span class="text-xs text-gray-400">{{ $read }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="text-center mt-10">
            <button class="px-8 py-3 border-2 border-[#1B2D6B] text-[#1B2D6B] font-semibold rounded-full hover:bg-[#1B2D6B] hover:text-white transition-colors">
                Load more articles
            </button>
        </div>
    </div>
</div>

{{-- Topics index --}}
<div class="max-w-5xl mx-auto px-6 py-16">
    <h2 class="text-3xl text-[#1B2D6B] text-center mb-2" style="font-family:'DM Serif Display',serif">
        Browse by topic
    </h2>
    <p class="text-center text-gray-500 text-sm mb-10">
        Pick a treatment area and dive into our deep-dive guides.
    </p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['💇','Hair', 84],
            ['💅','Nails', 47],
            ['🧖','Skincare', 63],
            ['💄','Makeup', 38],
            ['💆','Massage', 21],
            ['🤨','Brows', 19],
            ['👁️','Lashes', 15],
            ['🌿','Wellness', 32],
        ] as [$emoji, $topic, $count])
            <a href="#" class="block p-5 bg-white rounded-2xl border border-gray-100 hover:border-[#C5E1F0] hover:bg-[#E8F4FB]/40 transition-all">
                <div class="text-3xl mb-2">{{ $emoji }}</div>
                <div class="font-semibold text-gray-900">{{ $topic }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ $count }} articles</div>
            </a>
        @endforeach
    </div>
</div>

{{-- Newsletter --}}
<div class="bg-[#1B2D6B] py-16">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="text-3xl text-white mb-3" style="font-family:'DM Serif Display',serif">
            The Edit, in your inbox
        </h2>
        <p class="text-white/70 text-sm mb-6 max-w-lg mx-auto">
            New treatment guides, salon profiles, and trend reports — once a fortnight, no fluff.
        </p>

        @if (session('success'))
            <div class="max-w-md mx-auto mb-4 p-3 rounded-full bg-white/10 border border-white/20 text-sm text-white">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
            @csrf
            <input type="email" name="email" required placeholder="you@example.com"
                   value="{{ old('email') }}"
                   class="flex-1 px-4 py-3 rounded-full bg-white/10 border border-white/20 text-white placeholder:text-white/50 outline-none focus:bg-white/15" />
            <button type="submit"
                    class="px-6 py-3 bg-white text-[#1B2D6B] font-bold rounded-full hover:bg-[#E8F4FB] transition-colors">
                Subscribe
            </button>
        </form>

        @error('email')
            <p class="text-xs text-red-300 mt-2">{{ $message }}</p>
        @enderror

        <p class="text-xs text-white/50 mt-3">
            We'll never share your email. Unsubscribe any time.
        </p>
    </div>
</div>

</x-layouts.public>

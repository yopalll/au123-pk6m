<x-layouts.public title="Home">

{{-- ───── HERO ───────────────────────────────────────────────────────────── --}}
<section class="relative bg-[#1B2D6B] overflow-hidden min-h-[480px] flex items-center">
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);background-size:48px 48px"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-transparent via-[#4BA3CC]/20 to-transparent"></div>

    <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 text-center">
        <div class="inline-block bg-[#4BA3CC]/20 text-[#4BA3CC] border border-[#4BA3CC]/30 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-widest mb-5">
            ✦ UK Beauty Marketplace
        </div>
        <h1 class="text-5xl md:text-6xl text-white mb-4 leading-tight">
            Find Your Next<br /><em class="text-[#4BA3CC]">Beauty</em> Appointment
        </h1>
        <p class="text-white/60 text-lg mb-10 font-light">5,700+ professional salons across the United Kingdom</p>

        <form action="{{ route('cari') }}" method="GET"
              class="bg-white rounded-2xl p-2 flex items-center gap-0 shadow-2xl max-w-2xl mx-auto">
            <div class="flex-1 px-4 py-2 border-r border-gray-100 text-left">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Treatment</div>
                <input name="q" placeholder="e.g. Haircut, Manicure, Massage…"
                       class="w-full text-sm outline-none text-gray-800 placeholder-gray-300" />
            </div>
            <div class="flex-1 px-4 py-2 text-left">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Location</div>
                <input name="lokasi" placeholder="e.g. London, Manchester…"
                       class="w-full text-sm outline-none text-gray-800 placeholder-gray-300" />
            </div>
            <button type="submit"
                    class="flex-shrink-0 w-12 h-12 bg-[#1B2D6B] text-white rounded-xl flex items-center justify-center hover:bg-[#4BA3CC] transition-colors mx-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
            </button>
        </form>

        <div class="flex flex-wrap gap-2 justify-center mt-5">
            @foreach(['Haircut','Manicure','Facial','Massage','Brows','Makeup'] as $chip)
                <a href="{{ route('cari', ['q' => $chip]) }}"
                   class="bg-white/10 hover:bg-[#4BA3CC]/20 border border-white/15 text-white/80 hover:text-white text-xs rounded-full px-4 py-1.5 transition-all">
                    {{ $chip }}
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ───── STATS BAR ───────────────────────────────────────────────────────── --}}
<div class="bg-[#E8F4FB] border-b border-[#C5E1F0]">
    <div class="max-w-7xl mx-auto px-6 py-5 flex flex-wrap justify-center gap-10">
        @foreach([['5,700+','Salons listed'],['190K+','Treatments available'],['4.8★','Average rating'],['1,700+','Cities covered']] as [$n,$l])
            <div class="text-center">
                <div class="text-2xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $n }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $l }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- ───── CATEGORIES ─────────────────────────────────────────────────────── --}}
<section class="max-w-7xl mx-auto px-6 py-12">
    <h2 class="text-2xl text-[#1B2D6B] mb-6">Explore Categories</h2>
    <div class="flex gap-5 overflow-x-auto pb-2 scrollbar-hide">
        @foreach([
            ['hair','💇','Hair'],['facial','🧖','Face'],
            ['massage','💆','Massage'],['nail','💅','Nails'],
            ['brow','🤨','Brows'],['makeup','💄','Makeup'],
            ['body','🛁','Body'],['men','🪒',"Men's"],
        ] as [$q,$emoji,$label])
            <a href="{{ route('cari', ['q' => $q]) }}"
               class="flex-shrink-0 flex flex-col items-center gap-2 group">
                <div class="w-16 h-16 rounded-full bg-[#E8F4FB] border-2 border-[#C5E1F0] flex items-center justify-center text-2xl
                             group-hover:bg-[#1B2D6B] group-hover:border-[#1B2D6B] group-hover:-translate-y-1 transition-all duration-200">
                    {{ $emoji }}
                </div>
                <span class="text-xs font-medium text-gray-600 group-hover:text-[#1B2D6B]">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ───── POPULAR SALONS ────────────────────────────────────────────────── --}}
<section class="max-w-7xl mx-auto px-6 pb-12">
    <div class="flex items-baseline justify-between mb-6">
        <h2 class="text-2xl text-[#1B2D6B]">Popular Salons</h2>
        <a href="{{ route('cari') }}" class="text-sm text-[#4BA3CC] font-medium hover:underline">View all →</a>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse ($salons ?? [] as $salon)
            <x-salon-card :salon="$salon" layout="list" />
        @empty
            <p class="text-gray-400 py-8 text-center">No salons available yet.</p>
        @endforelse
    </div>
</section>

{{-- ───── HOW IT WORKS ──────────────────────────────────────────────────── --}}
<section class="bg-gray-50 py-16">
    <div class="max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-2">How It Works</div>
        <h2 class="text-3xl text-[#1B2D6B] mb-10">Book in 3 Simple Steps</h2>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['🔍','01','Find a Salon','Search by treatment, location or rating. Filter to match exactly what you need.'],
                ['📅','02','Pick a Time','Browse availability and pick the slot that works best for your schedule.'],
                ['✨','03','Enjoy Your Treatment','Walk in, get treated. Pay directly at the salon — no surprises.'],
            ] as [$icon,$n,$title,$desc])
                <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="w-14 h-14 rounded-full bg-[#E8F4FB] border-2 border-[#C5E1F0] flex items-center justify-center text-2xl mx-auto mb-4">
                        {{ $icon }}
                    </div>
                    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider mb-2">Step {{ $n }}</div>
                    <h3 class="text-xl text-[#1B2D6B] mb-2">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ───── CTA ───────────────────────────────────────────────────────────── --}}
<section class="bg-[#1B2D6B] pt-20 pb-24 text-center">
    <h2 class="text-4xl text-white mb-4">Ready to Look Your Best?</h2>
    <p class="text-white/60 text-lg mb-8">Join thousands of customers booking treatments on VIYGO every day</p>
    <div class="flex gap-3 justify-center flex-wrap">
        <a href="{{ route('cari') }}"
           class="px-8 py-3 bg-white text-[#1B2D6B] font-semibold rounded-full hover:bg-[#E8F4FB] transition-colors">
            Book Now
        </a>
        <a href="{{ route('mitra') }}"
           class="px-8 py-3 border-2 border-white/30 text-white font-medium rounded-full hover:border-white hover:bg-white/5 transition-all">
            List Your Salon
        </a>
    </div>
</section>

</x-layouts.public>

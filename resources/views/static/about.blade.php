<x-layouts.public title="About VIYGO">

{{-- Hero --}}
<section class="bg-[#1B2D6B] py-20 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Our Story</div>
    <h1 class="text-5xl text-white mb-4">About VIYGO</h1>
    <p class="text-white/60 text-lg max-w-2xl mx-auto">
        We're on a mission to make beauty &amp; wellness booking effortless for everyone.
    </p>
</section>

<div class="max-w-4xl mx-auto px-6 py-16 space-y-12">

    {{-- Mission image placeholder — see README-GAMBAR-STATIS.md (img-about-hero) --}}
    <div class="aspect-[16/7] rounded-2xl bg-linear-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-6xl">
        💆‍♀️
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Our Mission</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Every great salon deserves to be discovered, and every customer deserves a frictionless
                booking experience. VIYGO bridges that gap with real-time availability, transparent
                pricing and verified reviews.
            </p>
        </div>
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Our Story</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                Founded as a Treatwell-inspired marketplace, VIYGO has grown to feature 8,750+ professional
                salons across the United Kingdom, offering 190,000+ treatments through one elegant interface.
            </p>
        </div>
        <div>
            <h2 class="text-xl text-[#1B2D6B] mb-3">Our Promise</h2>
            <p class="text-sm text-gray-600 leading-relaxed">
                No surprise fees, no fake reviews, and a real human behind every help request.
                We win when both salons and their customers leave happy.
            </p>
        </div>
    </div>

    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-10 text-center">
        <h2 class="text-2xl text-[#1B2D6B] mb-3">By the numbers</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
            @foreach ([
                ['8,750+', 'Salon partners'],
                ['190K+',  'Treatments listed'],
                ['1,700+', 'Cities covered'],
                ['4.8★',   'Average rating'],
            ] as [$n, $l])
                <div>
                    <div class="text-3xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $n }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ $l }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('cari') }}"
           class="inline-block px-8 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
            Find a salon →
        </a>
    </div>
</div>

</x-layouts.public>

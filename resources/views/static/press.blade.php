<x-layouts.public title="Press">

<section class="bg-[#1B2D6B] py-20 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Press &amp; media</div>
    <h1 class="text-5xl text-white mb-4">Press</h1>
    <p class="text-white/60 text-lg max-w-2xl mx-auto">
        Latest news, brand assets and interview enquiries.
    </p>
</section>

<div class="max-w-4xl mx-auto px-6 py-16 space-y-10">

    <h2 class="text-2xl text-[#1B2D6B]">In the news</h2>

    <div class="space-y-4">
        @foreach ([
            ['VIYGO partners with 8,750+ UK salons', 'TechCrunch UK', 'May 2026'],
            ['How VIYGO is reshaping beauty bookings', 'The Times Style', 'April 2026'],
            ['Inside VIYGO\'s data pipeline', 'The Verge', 'March 2026'],
        ] as [$title, $outlet, $date])
            <div class="flex items-start justify-between border-b border-gray-100 pb-4">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                    <p class="text-xs text-gray-400 mt-1">{{ $outlet }} · {{ $date }}</p>
                </div>
                <span class="text-xs text-gray-300">→</span>
            </div>
        @endforeach
    </div>

    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-6 text-center">
        <h3 class="font-semibold text-[#1B2D6B] mb-2">Press enquiries</h3>
        <p class="text-sm text-gray-600 mb-4">For interviews, brand assets or stats requests, please email us.</p>
        <a href="mailto:press@viygo.com"
           class="inline-block px-6 py-2.5 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold hover:bg-[#4BA3CC] transition-colors">
            press@viygo.com
        </a>
    </div>
</div>

</x-layouts.public>

<x-layouts.public title="Treatment Files">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Treatment Guides</div>
    <h1 class="text-4xl text-white mb-4">The Treatment Files</h1>
    <p class="text-white/60 text-lg max-w-xl mx-auto">Articles, tips and expert guides on every beauty treatment imaginable</p>
</div>

<div class="max-w-5xl mx-auto px-6 py-12">
    {{-- Featured --}}
    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <div class="bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] rounded-2xl p-8 flex flex-col justify-between min-h-64">
            <div>
                <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Featured</span>
                <h2 class="text-2xl text-[#1B2D6B] mt-2 mb-3">The Complete Hair Care Guide for 2026</h2>
                <p class="text-sm text-gray-600">Tips from professional stylists to keep your hair healthy and shiny all year round.</p>
            </div>
            <a href="#" class="mt-4 text-sm text-[#1B2D6B] font-semibold hover:underline">Read More →</a>
        </div>
        <div class="grid grid-rows-2 gap-4">
            @foreach([
                'The 5 Biggest Nail Art Trends This Season',
                'How to Care for Your Skin After a Facial',
            ] as $title)
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 hover:border-[#C5E1F0] hover:bg-[#E8F4FB]/40 transition-all cursor-pointer">
                    <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Tips</span>
                    <h3 class="text-base font-semibold text-gray-900 mt-1 mb-1">{{ $title }}</h3>
                    <a href="#" class="text-xs text-[#4BA3CC] hover:underline">Read →</a>
                </div>
            @endforeach
        </div>
    </div>

    <h2 class="text-xl text-[#1B2D6B] mb-6">Latest Articles</h2>
    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['How to Pick the Right Salon for Your Wedding','Hair'],
            ['Gel vs Regular Manicure & Pedicure: Which is Best?','Nails'],
            ['The Mental Health Benefits of Regular Massages','Massage'],
            ['Hair Colour Trends to Try in 2026','Hair'],
            ['Hydra Facial vs Classic Facial: Which Suits You?','Facial'],
            ['Tips for Brows That Stay Full and Natural','Brows'],
        ] as [$title, $cat])
            <div class="group cursor-pointer">
                <div class="h-40 bg-[#E8F4FB] rounded-xl mb-3 flex items-center justify-center text-4xl border border-[#C5E1F0] group-hover:border-[#4BA3CC] transition-colors">
                    {{ ['Hair'=>'💇','Nails'=>'💅','Massage'=>'💆','Facial'=>'🧖','Brows'=>'🤨'][$cat] ?? '✨' }}
                </div>
                <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">{{ $cat }}</span>
                <h3 class="text-sm font-semibold text-gray-900 mt-1 group-hover:text-[#1B2D6B] transition-colors">{{ $title }}</h3>
                <a href="#" class="text-xs text-[#4BA3CC] mt-1 inline-block hover:underline">Read more →</a>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.public>

<x-layouts.public title="Lookbook">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Style Inspiration</div>
    <h1 class="text-4xl text-white mb-4">Lookbook</h1>
    <p class="text-white/60 text-lg">Discover the latest looks from top salons across the UK</p>
</div>

<div class="max-w-6xl mx-auto px-6 py-10">
    <div class="flex gap-2 mb-8 flex-wrap">
        @foreach(['All','Hair','Nails','Makeup','Brows','Skincare'] as $cat)
            <button class="px-5 py-2 rounded-full border {{ $loop->first ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }} text-sm font-medium transition-colors">
                {{ $cat }}
            </button>
        @endforeach
    </div>

    <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
        @php
            $emojis  = ['💇','💅','💄','🤨','🧖','✂️','💆','🪮','🌿','💋','👁️','🎨'];
            $titles  = ['Hair','Nails','Makeup','Brows','Skin','Cuts','Massage','Styling','Spa','Lip','Lash','Colour'];
        @endphp
        @for ($i = 0; $i < 12; $i++)
            <div class="break-inside-avoid rounded-xl overflow-hidden bg-[#E8F4FB] border border-[#C5E1F0] group cursor-pointer">
                <div class="aspect-{{ $i % 3 === 0 ? 'square' : ($i % 3 === 1 ? '[3/4]' : '[4/5]') }} bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-4xl">
                    {{ $emojis[$i] }}
                </div>
                <div class="p-3">
                    <p class="text-xs font-semibold text-gray-800">Latest {{ $titles[$i] }} Looks</p>
                    <p class="text-xs text-[#4BA3CC] mt-0.5 hover:underline">View salons →</p>
                </div>
            </div>
        @endfor
    </div>
</div>
</x-layouts.public>

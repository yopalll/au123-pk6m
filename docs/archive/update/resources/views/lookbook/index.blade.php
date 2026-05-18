<x-layouts.public title="Lookbook VIYGO">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Inspirasi Gaya</div>
    <h1 class="text-4xl text-white mb-4">Lookbook</h1>
    <p class="text-white/60 text-lg">Temukan inspirasi gaya terbaru dari ribuan salon terbaik Indonesia</p>
</div>

<div class="max-w-6xl mx-auto px-6 py-10">
    {{-- Category filter --}}
    <div class="flex gap-2 mb-8 flex-wrap">
        @foreach(['Semua','Rambut','Kuku','Makeup','Alis','Perawatan'] as $cat)
            <button class="px-5 py-2 rounded-full border {{ $loop->first ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]' : 'border-gray-200 text-gray-700 hover:border-[#1B2D6B]' }} text-sm font-medium transition-colors">
                {{ $cat }}
            </button>
        @endforeach
    </div>

    {{-- Masonry-style grid --}}
    <div class="columns-2 md:columns-3 lg:columns-4 gap-4 space-y-4">
        @for ($i = 0; $i < 12; $i++)
            <div class="break-inside-avoid rounded-xl overflow-hidden bg-[#E8F4FB] border border-[#C5E1F0] group cursor-pointer">
                <div class="aspect-{{ $i % 3 === 0 ? 'square' : ($i % 3 === 1 ? '[3/4]' : '[4/5]') }} bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-4xl">
                    {{ ['💇','💅','💄','🤨','🧖','✂️','💆','🪮','🌿','💋','👁️','🎨'][$i] }}
                </div>
                <div class="p-3">
                    <p class="text-xs font-semibold text-gray-800">Gaya {{ ['Rambut','Kuku','Makeup','Alis','Wajah','Potong','Pijat','Styling','Spa','Lip','Lash','Color'][$i] }} Terbaru</p>
                    <p class="text-xs text-[#4BA3CC] mt-0.5 hover:underline">Lihat salon →</p>
                </div>
            </div>
        @endfor
    </div>
</div>
</x-layouts.public>

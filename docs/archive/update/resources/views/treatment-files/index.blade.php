<x-layouts.public title="Treatment Files">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Panduan Perawatan</div>
    <h1 class="text-4xl text-white mb-4">The Treatment Files</h1>
    <p class="text-white/60 text-lg max-w-xl mx-auto">Artikel, tips, dan panduan lengkap tentang perawatan diri dari para ahli kecantikan Indonesia</p>
</div>

<div class="max-w-5xl mx-auto px-6 py-12">
    {{-- Featured --}}
    <div class="grid md:grid-cols-2 gap-8 mb-12">
        <div class="bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0] rounded-2xl p-8 flex flex-col justify-between min-h-64">
            <div>
                <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Featured</span>
                <h2 class="text-2xl text-[#1B2D6B] mt-2 mb-3">Panduan Lengkap Perawatan Rambut 2026</h2>
                <p class="text-sm text-gray-600">Tips dari para hair stylist profesional untuk menjaga rambut tetap sehat dan berkilau sepanjang tahun.</p>
            </div>
            <a href="#" class="mt-4 text-sm text-[#1B2D6B] font-semibold hover:underline">Baca Selengkapnya →</a>
        </div>
        <div class="grid grid-rows-2 gap-4">
            @foreach(['5 Tren Nail Art Terpopuler Musim Ini','Panduan Merawat Kulit Wajah Setelah Facial'] as $title)
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 hover:border-[#C5E1F0] hover:bg-[#E8F4FB]/40 transition-all cursor-pointer">
                    <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Tips</span>
                    <h3 class="text-base font-semibold text-gray-900 mt-1 mb-1">{{ $title }}</h3>
                    <a href="#" class="text-xs text-[#4BA3CC] hover:underline">Baca →</a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Article grid --}}
    <h2 class="text-xl text-[#1B2D6B] mb-6">Artikel Terbaru</h2>
    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['Cara Memilih Salon yang Tepat untuk Acara Pernikahan','Rambut'],
            ['Perbedaan Manikur dan Pedikur Gel vs Biasa','Kuku'],
            ['Manfaat Pijat Rutin untuk Kesehatan Mental','Pijat'],
            ['Tren Warna Rambut 2026 yang Wajib Dicoba','Rambut'],
            ['Facial Hydra vs Facial Biasa, Mana yang Cocok?','Facial'],
            ['Tips Merawat Alis agar Tetap Tebal dan Natural','Alis'],
        ] as [$title, $cat])
            <div class="group cursor-pointer">
                <div class="h-40 bg-[#E8F4FB] rounded-xl mb-3 flex items-center justify-center text-4xl border border-[#C5E1F0] group-hover:border-[#4BA3CC] transition-colors">
                    {{ ['Rambut'=>'💇','Kuku'=>'💅','Pijat'=>'💆','Facial'=>'🧖','Alis'=>'🤨'][$cat] ?? '✨' }}
                </div>
                <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">{{ $cat }}</span>
                <h3 class="text-sm font-semibold text-gray-900 mt-1 group-hover:text-[#1B2D6B] transition-colors">{{ $title }}</h3>
                <a href="#" class="text-xs text-[#4BA3CC] mt-1 inline-block hover:underline">Baca selengkapnya →</a>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.public>

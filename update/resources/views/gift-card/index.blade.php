<x-layouts.public title="Gift Card VIYGO">
<div class="bg-[#1B2D6B] py-16">
    <div class="max-w-4xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Hadiah Terbaik</div>
        <h1 class="text-4xl text-white mb-4">Gift Card VIYGO</h1>
        <p class="text-white/60 text-lg max-w-xl mx-auto">Berikan hadiah perawatan terbaik untuk orang yang kamu sayangi. Berlaku di semua salon VIYGO.</p>
    </div>
</div>

<div class="max-w-4xl mx-auto px-6 py-12">
    <div class="grid md:grid-cols-2 gap-8 items-center mb-16">
        <div>
            <h2 class="text-2xl text-[#1B2D6B] mb-4">Cara Penggunaan</h2>
            <div class="space-y-4">
                @foreach(['Pilih nominal gift card','Kirim via email atau cetak sendiri','Penerima gunakan kode saat booking','Potongan otomatis di kasir salon'] as $i => $step)
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">{{ $i+1 }}</div>
                        <span class="text-gray-700">{{ $step }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="bg-gradient-to-br from-[#1B2D6B] to-[#4BA3CC] rounded-2xl p-8 text-white text-center shadow-xl">
            <div class="text-5xl mb-4">🎁</div>
            <div class="text-3xl font-bold mb-2">VIYGO</div>
            <div class="text-white/70 text-sm mb-4">Gift Card</div>
            <div class="bg-white/20 rounded-lg px-4 py-2 font-mono text-sm">VIYGO-XXXX-XXXX</div>
        </div>
    </div>

    <h2 class="text-2xl text-[#1B2D6B] mb-6 text-center">Pilih Nominal</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        @foreach([50000, 100000, 200000, 500000] as $nominal)
            <div class="border-2 border-gray-200 hover:border-[#1B2D6B] rounded-xl p-6 text-center cursor-pointer transition-all group hover:-translate-y-1">
                <div class="text-2xl font-bold text-[#1B2D6B] group-hover:scale-110 transition-transform">
                    Rp {{ number_format($nominal, 0, ',', '.') }}
                </div>
                <button class="mt-3 px-4 py-1.5 bg-[#1B2D6B] text-white text-xs font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                    Beli Sekarang
                </button>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.public>

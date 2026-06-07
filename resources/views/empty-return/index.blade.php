<x-layouts.public title="Empty Return">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

    <div class="rounded-3xl bg-gradient-to-br from-emerald-600 to-teal-500 text-white p-8 sm:p-12 mb-10">
        <p class="text-xs uppercase tracking-widest opacity-80 mb-2">♻️ Peduli Lingkungan</p>
        <h1 class="text-3xl sm:text-4xl font-bold mb-3" style="font-family:'DM Serif Display',serif">Empty Return Program</h1>
        <p class="text-sm opacity-90 max-w-lg mb-6">Kembalikan botol kosong skincare-mu untuk didaur ulang. Dapatkan <strong>poin belanja</strong> + akses <strong>konten eksklusif</strong>. Rawat kulit, jaga bumi.</p>
        <a href="{{ route('emptyReturn.create') }}" class="inline-block px-6 py-3 bg-white text-emerald-700 text-sm font-semibold rounded-full hover:bg-gray-100 transition-colors">Kembalikan Botol Sekarang</a>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-10">
        <div class="bg-white border border-gray-100 rounded-2xl p-6 text-center">
            <p class="text-3xl font-bold text-emerald-600">{{ number_format($totalBotol, 0, ',', '.') }}</p>
            <p class="text-sm text-gray-500 mt-1">Botol Dikembalikan</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-6 text-center">
            <p class="text-3xl font-bold text-emerald-600">{{ number_format($estimasiKg, 1, ',', '.') }} kg</p>
            <p class="text-sm text-gray-500 mt-1">Sampah Plastik Dicegah</p>
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-4 mb-10">
        @foreach ([['1','Daftarkan','Isi form & upload foto botol kosong'],['2','Verifikasi','Admin cek dalam 1-3 hari kerja'],['3','Dapat Poin','Poin masuk + tier naik']] as [$n,$t,$d])
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center mb-3">{{ $n }}</span>
                <h3 class="font-semibold">{{ $t }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $d }}</p>
            </div>
        @endforeach
    </div>

    {{-- Tier --}}
    <h2 class="text-xl font-semibold mb-4" style="font-family:'DM Serif Display',serif">Tier & Reward</h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ([['Starter','0','Submit & dapat poin'],['Bronze 🥉','50','+5 konten eksklusif'],['Silver 🥈','150','+15 konten + free ongkir 1x/bln'],['Gold 🥇','300','Semua konten + free ongkir unlimited']] as [$tier,$min,$benefit])
            <div class="bg-white border border-gray-100 rounded-2xl p-4 text-center">
                <p class="font-semibold text-sm">{{ $tier }}</p>
                <p class="text-2xl font-bold text-emerald-600 my-1">{{ $min }}</p>
                <p class="text-[11px] text-gray-500">{{ $benefit }}</p>
            </div>
        @endforeach
    </div>

    @auth
        <div class="text-center mt-8">
            <a href="{{ route('emptyReturn.history') }}" class="text-sm text-emerald-600 hover:underline">Lihat riwayat pengembalianku →</a>
        </div>
    @endauth
</div>
</x-layouts.public>

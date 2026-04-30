<x-layouts.public title="VIYGO Rewards">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Program Loyalitas</div>
    <h1 class="text-4xl text-white mb-4">VIYGO Rewards</h1>
    <p class="text-white/60 text-lg">Kumpulkan poin setiap booking & tukar dengan voucher diskon</p>
</div>
<div class="max-w-3xl mx-auto px-6 py-12">
    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-8 text-center mb-10">
        <div class="text-5xl font-bold text-[#1B2D6B] mb-1">0</div>
        <div class="text-gray-500 text-sm mb-4">Poin Saat Ini</div>
        <div class="w-full bg-[#C5E1F0] rounded-full h-2 mb-2">
            <div class="bg-[#1B2D6B] h-2 rounded-full" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-500">0 / 1500 poin untuk voucher Rp 50.000</div>
    </div>
    <h2 class="text-xl text-[#1B2D6B] mb-6">Cara Mendapatkan Poin</h2>
    <div class="grid md:grid-cols-3 gap-4">
        @foreach([['📅','Booking Layanan','Dapatkan 10 poin setiap Rp 10.000 yang kamu bayar'],['⭐','Tulis Ulasan','Dapat 50 poin setiap kali menulis ulasan salon'],['👥','Ajak Teman','Dapatkan 200 poin untuk setiap teman yang bergabung']] as [$i,$t,$d])
            <div class="text-center p-5 bg-gray-50 rounded-xl border border-gray-100">
                <div class="text-3xl mb-3">{{ $i }}</div>
                <div class="font-semibold text-gray-900 mb-1">{{ $t }}</div>
                <div class="text-xs text-gray-500">{{ $d }}</div>
            </div>
        @endforeach
    </div>
</div>
</x-layouts.public>

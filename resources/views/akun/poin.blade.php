<x-layouts.public title="Poin & Reward">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">Poin & Reward</h1>

    {{-- Saldo + tier --}}
    <div class="rounded-3xl bg-gradient-to-br from-[#1B2D6B] to-[#4BA3CC] text-white p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs opacity-80 uppercase tracking-wide">Saldo Poin</p>
                <p class="text-4xl font-bold">{{ number_format($userPoint->saldo, 0, ',', '.') }}</p>
                <p class="text-xs opacity-80 mt-1">≈ Rp {{ number_format($userPoint->saldo * 1000, 0, ',', '.') }} potongan</p>
            </div>
            <span class="px-4 py-2 bg-white/20 rounded-full text-sm font-semibold uppercase">{{ $userPoint->tier }}</span>
        </div>
        @if ($nextTier)
            <div class="mt-5">
                <div class="flex justify-between text-xs opacity-90 mb-1">
                    <span>Menuju {{ ucfirst($nextTier) }}</span>
                    <span>{{ $userPoint->total_earned }} / {{ $nextThreshold }}</span>
                </div>
                <div class="w-full h-2 bg-white/20 rounded-full overflow-hidden">
                    <div class="h-full bg-white rounded-full" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @else
            <p class="text-sm mt-4 opacity-90">🏆 Kamu sudah di tier tertinggi!</p>
        @endif
    </div>

    {{-- Badges --}}
    @if ($badges->count())
        <div class="mb-6">
            <h2 class="font-semibold mb-2">Badge</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($badges as $b)
                    <span class="text-sm px-3 py-1.5 bg-amber-50 text-amber-700 rounded-full">{{ \App\Services\BadgeService::label($b->badge_slug) }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        <a href="{{ route('emptyReturn.create') }}" class="bg-emerald-50 rounded-2xl p-5 hover:bg-emerald-100 transition-colors">
            <p class="text-2xl mb-1">♻️</p><p class="font-semibold text-sm">Kembalikan Botol</p><p class="text-xs text-gray-500">Dapat poin lagi</p>
        </a>
        <a href="{{ route('exclusive.index') }}" class="bg-[#E8F4FB] rounded-2xl p-5 hover:bg-[#C5E1F0] transition-colors">
            <p class="text-2xl mb-1">🔒</p><p class="font-semibold text-sm">Konten Eksklusif</p><p class="text-xs text-gray-500">{{ $exclusiveContents->count() }} konten tersedia</p>
        </a>
    </div>

    {{-- Riwayat transaksi --}}
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Riwayat Poin</h2>
        <a href="{{ route('akun.poin.history') }}" class="text-sm text-[#4BA3CC] hover:underline">Lihat semua →</a>
    </div>
    <div class="space-y-2">
        @forelse ($recentTransactions as $t)
            <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-4 py-3">
                <div>
                    <p class="text-sm">{{ $t->description }}</p>
                    <p class="text-xs text-gray-400">{{ $t->created_at?->format('d M Y, H:i') }}</p>
                </div>
                <span class="text-sm font-semibold {{ $t->type === 'earn' ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $t->type === 'earn' ? '+' : '−' }}{{ $t->amount }}
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada transaksi poin.</p>
        @endforelse
    </div>
</div>
</x-layouts.public>

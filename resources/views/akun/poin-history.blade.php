<x-layouts.public title="Riwayat Poin">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('akun.poin') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Poin & Reward</a>
    <h1 class="text-2xl font-semibold mt-3 mb-6" style="font-family:'DM Serif Display',serif">Riwayat Poin</h1>

    <div class="space-y-2">
        @forelse ($transactions as $t)
            <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-4 py-3">
                <div>
                    <p class="text-sm">{{ $t->description }}</p>
                    <p class="text-xs text-gray-400">{{ $t->created_at?->format('d M Y, H:i') }} · saldo: {{ $t->saldo_after }}</p>
                </div>
                <span class="text-sm font-semibold {{ $t->type === 'earn' ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $t->type === 'earn' ? '+' : '−' }}{{ $t->amount }}
                </span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada transaksi poin.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $transactions->links() }}</div>
</div>
</x-layouts.public>

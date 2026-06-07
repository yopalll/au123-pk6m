<x-layouts.public title="Leaderboard">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('komunitas.index') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Komunitas</a>
    <h1 class="text-2xl font-semibold mt-3 mb-6" style="font-family:'DM Serif Display',serif">🏆 Leaderboard Kontributor</h1>

    @forelse ($leaders as $i => $l)
        <div class="flex items-center gap-4 bg-white border border-gray-100 rounded-2xl p-4 mb-2">
            <span class="w-8 text-center font-bold {{ $i < 3 ? 'text-amber-500 text-xl' : 'text-gray-400' }}">{{ ['🥇','🥈','🥉'][$i] ?? ($i+1) }}</span>
            <div class="w-10 h-10 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center font-bold">
                {{ strtoupper(substr($l->user->full_name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex-1">
                <p class="font-medium text-sm">{{ $l->user->full_name ?? 'User' }}</p>
            </div>
            <span class="text-sm font-bold text-[#1B2D6B]">{{ number_format($l->total_points, 0, ',', '.') }} poin</span>
        </div>
    @empty
        <p class="text-center text-gray-400 py-16">Belum ada kontributor. Mulai buat thread!</p>
    @endforelse
</div>
</x-layouts.public>

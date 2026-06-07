<x-layouts.public title="Bookmark Forum">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">🔖 Bookmark Forum</h1>

    @forelse ($bookmarks as $bm)
        @if ($bm->thread)
            <a href="{{ route('komunitas.thread.show', $bm->thread->slug) }}" class="block bg-white border border-gray-100 rounded-2xl p-4 mb-2 hover:border-[#4BA3CC]">
                <p class="font-medium text-sm">{{ $bm->thread->judul }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $bm->thread->category->nama ?? '' }} · {{ $bm->thread->user->full_name ?? 'User' }}</p>
            </a>
        @endif
    @empty
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">🔖</p>
            <p class="mb-4">Belum ada thread yang di-bookmark.</p>
            <a href="{{ route('komunitas.index') }}" class="inline-block px-5 py-2.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Jelajahi Komunitas</a>
        </div>
    @endforelse

    <div class="mt-6">{{ $bookmarks->links() }}</div>
</div>
</x-layouts.public>

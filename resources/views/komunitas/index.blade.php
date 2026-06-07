<x-layouts.public title="Komunitas">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="rounded-3xl bg-gradient-to-br from-[#1B2D6B] to-[#4BA3CC] text-white p-8 mb-8">
        <h1 class="text-3xl font-bold mb-2" style="font-family:'DM Serif Display',serif">💬 Komunitas VIYGO</h1>
        <p class="text-sm opacity-90 mb-4">Diskusi, tips, dan review skincare bareng sesama beauty enthusiast.</p>
        <div class="flex gap-6 text-sm">
            <span><strong class="text-lg">{{ $stats['member'] }}</strong> member</span>
            <span><strong class="text-lg">{{ $stats['thread'] }}</strong> thread</span>
            <span><strong class="text-lg">{{ $stats['reply'] }}</strong> balasan</span>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold" style="font-family:'DM Serif Display',serif">Kategori</h2>
        <div class="flex gap-2">
            <a href="{{ route('komunitas.leaderboard') }}" class="text-sm px-4 py-2 border border-gray-200 rounded-full hover:border-[#4BA3CC]">🏆 Leaderboard</a>
            <a href="{{ route('komunitas.thread.create') }}" class="text-sm px-4 py-2 bg-[#1B2D6B] text-white rounded-full">+ Buat Thread</a>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-3 mb-10">
        @foreach ($categories as $cat)
            <a href="{{ route('komunitas.kategori', $cat->slug) }}" class="flex items-center gap-4 bg-white border border-gray-100 rounded-2xl p-4 hover:border-[#4BA3CC] transition-colors">
                <span class="text-2xl">{{ $cat->icon }}</span>
                <div class="flex-1">
                    <p class="font-medium text-sm">{{ $cat->nama }}</p>
                    <p class="text-xs text-gray-400">{{ $cat->deskripsi }}</p>
                </div>
                <span class="text-xs text-gray-400">{{ $cat->threads_count }}</span>
            </a>
        @endforeach
    </div>

    <div class="grid md:grid-cols-2 gap-8">
        <section>
            <h2 class="font-semibold mb-3">🔥 Trending</h2>
            @forelse ($trendingThreads as $t)
                <a href="{{ route('komunitas.thread.show', $t->slug) }}" class="block bg-white border border-gray-100 rounded-xl p-3 mb-2 hover:border-[#4BA3CC]">
                    <p class="text-sm font-medium line-clamp-1">{{ $t->judul }}</p>
                    <p class="text-xs text-gray-400">{{ $t->category->nama }} · ♥ {{ $t->like_count }} · 💬 {{ $t->reply_count }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">Belum ada thread.</p>
            @endforelse
        </section>
        <section>
            <h2 class="font-semibold mb-3">🆕 Terbaru</h2>
            @forelse ($recentThreads as $t)
                <a href="{{ route('komunitas.thread.show', $t->slug) }}" class="block bg-white border border-gray-100 rounded-xl p-3 mb-2 hover:border-[#4BA3CC]">
                    <p class="text-sm font-medium line-clamp-1">{{ $t->judul }}</p>
                    <p class="text-xs text-gray-400">{{ $t->user->full_name ?? 'User' }} · {{ $t->created_at->diffForHumans() }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">Belum ada thread.</p>
            @endforelse
        </section>
    </div>
</div>
</x-layouts.public>

<x-layouts.public :title="$kategori->nama">
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <nav class="text-sm text-gray-400 mb-4">
        <a href="{{ route('komunitas.index') }}" class="hover:text-[#1B2D6B]">Komunitas</a> /
        <span class="text-gray-700">{{ $kategori->nama }}</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">{{ $kategori->icon }} {{ $kategori->nama }}</h1>
        <a href="{{ route('komunitas.thread.create') }}" class="text-sm px-4 py-2 bg-[#1B2D6B] text-white rounded-full">+ Buat Thread</a>
    </div>

    @forelse ($threads as $t)
        <a href="{{ route('komunitas.thread.show', $t->slug) }}" class="block bg-white border border-gray-100 rounded-2xl p-4 mb-2 hover:border-[#4BA3CC]">
            <div class="flex items-center gap-2">
                @if ($t->is_pinned)<span class="text-xs">📌</span>@endif
                <p class="font-medium">{{ $t->judul }}</p>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $t->user->full_name ?? 'User' }} · {{ $t->created_at->diffForHumans() }} · 👁 {{ $t->view_count }} · ♥ {{ $t->like_count }} · 💬 {{ $t->reply_count }}</p>
        </a>
    @empty
        <div class="text-center py-16 text-gray-400">
            <p class="mb-4">Belum ada thread di kategori ini.</p>
            <a href="{{ route('komunitas.thread.create') }}" class="inline-block px-5 py-2.5 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Buat Thread Pertama</a>
        </div>
    @endforelse

    <div class="mt-6">{{ $threads->links() }}</div>
</div>
</x-layouts.public>

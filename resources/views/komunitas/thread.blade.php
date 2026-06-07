<x-layouts.public :title="$thread->judul">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <nav class="text-sm text-gray-400 mb-4">
        <a href="{{ route('komunitas.index') }}" class="hover:text-[#1B2D6B]">Komunitas</a> /
        <a href="{{ route('komunitas.kategori', $thread->category->slug) }}" class="hover:text-[#1B2D6B]">{{ $thread->category->nama }}</a>
    </nav>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif

    {{-- Thread --}}
    <article class="bg-white border border-gray-100 rounded-2xl p-6 mb-6">
        <h1 class="text-2xl font-semibold mb-2" style="font-family:'DM Serif Display',serif">{{ $thread->judul }}</h1>
        <p class="text-xs text-gray-400 mb-4">{{ $thread->user->full_name ?? 'User' }} · {{ $thread->created_at->diffForHumans() }} · 👁 {{ $thread->view_count }}</p>
        <div class="prose prose-sm max-w-none text-gray-700">{!! $thread->konten !!}</div>

        @if ($thread->taggedProducts->count())
            <div class="mt-5 pt-4 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Produk terkait</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($thread->taggedProducts as $p)
                        <a href="{{ route('shop.produk.show', $p->slug) }}" class="text-xs bg-[#E8F4FB] text-[#1B2D6B] px-3 py-1.5 rounded-full hover:bg-[#C5E1F0]">🧴 {{ $p->nama }}</a>
                    @endforeach
                </div>
            </div>
        @endif

        @auth
            <div class="mt-5 pt-4 border-t border-gray-100 flex gap-4"
                 x-data="{
                    liked: {{ $likedThread ? 'true' : 'false' }}, likes: {{ $thread->like_count }}, marked: {{ $isBookmarked ? 'true' : 'false' }},
                    hdr: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    like() { fetch('{{ route('komunitas.thread.like', $thread->slug) }}', {method:'POST',headers:this.hdr}).then(r=>r.json()).then(d=>{this.liked=d.liked;this.likes=d.like_count}); },
                    bookmark() { fetch('{{ route('komunitas.thread.bookmark', $thread->slug) }}', {method:'POST',headers:this.hdr}).then(r=>r.json()).then(d=>{this.marked=d.bookmarked}); }
                 }">
                <button @click="like()" :class="liked ? 'text-red-500' : 'text-gray-500'" class="text-sm flex items-center gap-1 hover:text-red-500">
                    <span x-text="liked ? '♥' : '♡'"></span> <span x-text="likes"></span>
                </button>
                <button @click="bookmark()" :class="marked ? 'text-[#1B2D6B]' : 'text-gray-500'" class="text-sm flex items-center gap-1 hover:text-[#1B2D6B]">
                    <span x-text="marked ? '🔖 Tersimpan' : '🔖 Bookmark'"></span>
                </button>
            </div>
        @else
            <div class="mt-5 pt-4 border-t border-gray-100 text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-[#4BA3CC] hover:underline">Login</a> untuk like, bookmark, & balas.
            </div>
        @endauth
    </article>

    {{-- Replies --}}
    <h2 class="font-semibold mb-3">{{ $thread->reply_count }} Balasan</h2>
    <div class="space-y-3 mb-6">
        @forelse ($thread->replies as $reply)
            <div class="bg-white border border-gray-100 rounded-2xl p-4">
                <p class="text-xs text-gray-400 mb-1">{{ $reply->user->full_name ?? 'User' }} · {{ $reply->created_at->diffForHumans() }}</p>
                <div class="text-sm text-gray-700">{!! $reply->konten !!}</div>
                <div class="mt-2 flex items-center gap-3" x-data="{ liked: {{ in_array($reply->id_reply, $likedReplies) ? 'true':'false' }}, likes: {{ $reply->like_count }} }">
                    @auth
                        <button @click="fetch('{{ route('komunitas.reply.like', $reply->id_reply) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{liked=d.liked;likes=d.like_count})"
                                :class="liked ? 'text-red-500':'text-gray-400'" class="text-xs flex items-center gap-1">
                            <span x-text="liked?'♥':'♡'"></span> <span x-text="likes"></span>
                        </button>
                    @else
                        <span class="text-xs text-gray-400">♥ {{ $reply->like_count }}</span>
                    @endauth
                </div>

                {{-- Nested children --}}
                @foreach ($reply->children as $child)
                    <div class="ml-6 mt-3 pl-3 border-l-2 border-gray-100">
                        <p class="text-xs text-gray-400 mb-1">{{ $child->user->full_name ?? 'User' }} · {{ $child->created_at->diffForHumans() }}</p>
                        <div class="text-sm text-gray-700">{!! $child->konten !!}</div>
                    </div>
                @endforeach

                @auth
                    @if (! $thread->is_locked)
                        <details class="mt-2">
                            <summary class="text-xs text-[#4BA3CC] cursor-pointer">Balas</summary>
                            <form method="POST" action="{{ route('komunitas.reply.store', $thread->slug) }}" class="mt-2 flex gap-2">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $reply->id_reply }}">
                                <input name="konten" required placeholder="Balas…" class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5">
                                <button class="text-xs px-3 py-1.5 bg-[#1B2D6B] text-white rounded-lg">Kirim</button>
                            </form>
                        </details>
                    @endif
                @endauth
            </div>
        @empty
            <p class="text-sm text-gray-400">Belum ada balasan. Jadilah yang pertama!</p>
        @endforelse
    </div>

    {{-- Reply form --}}
    @auth
        @if (! $thread->is_locked)
            <form method="POST" action="{{ route('komunitas.reply.store', $thread->slug) }}" class="bg-white border border-gray-100 rounded-2xl p-4">
                @csrf
                <textarea name="konten" rows="3" required placeholder="Tulis balasanmu…" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-[#4BA3CC]"></textarea>
                <button class="mt-2 px-5 py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Kirim Balasan</button>
            </form>
        @else
            <p class="text-sm text-gray-400 text-center py-4">🔒 Thread ini dikunci.</p>
        @endif
    @endauth
</div>

</x-layouts.public>

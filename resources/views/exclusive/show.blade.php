<x-layouts.public :title="$content->judul">
<article class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <a href="{{ route('exclusive.index') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Konten Eksklusif</a>
    <span class="inline-block mt-4 text-[10px] uppercase tracking-wide text-[#4BA3CC] font-semibold">{{ $content->tipe }} · min tier {{ $content->min_tier }}</span>
    <h1 class="text-3xl font-bold mt-2 mb-6" style="font-family:'DM Serif Display',serif">{{ $content->judul }}</h1>

    @if ($content->tipe === 'video' && $content->video_url)
        <div class="aspect-video rounded-2xl overflow-hidden mb-6 bg-black">
            <iframe src="{{ $content->video_url }}" class="w-full h-full" frameborder="0" allowfullscreen></iframe>
        </div>
    @endif

    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
        {!! nl2br(e($content->konten)) !!}
    </div>
</article>
</x-layouts.public>

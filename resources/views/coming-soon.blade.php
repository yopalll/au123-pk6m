<x-layouts.public :title="$title ?? 'Segera Hadir'">
    <div class="flex items-center justify-center min-h-[60vh] px-6">
        <div class="text-center text-gray-500">
            <p class="text-5xl mb-4">🚧</p>
            <h1 class="text-2xl font-semibold text-gray-700">{{ $title ?? 'Segera Hadir' }}</h1>
            <p class="text-sm mt-2">Fitur ini sedang dalam pengembangan (VIYGO V2).</p>
            <a href="{{ route('home') }}"
               class="inline-block mt-6 px-5 py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</x-layouts.public>

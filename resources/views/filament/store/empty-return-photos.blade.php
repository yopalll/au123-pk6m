<div class="space-y-4">
    @forelse ($record->photos as $photo)
        {{-- Stream lewat route, bukan /storage langsung — agar tetap terbuka di
             produksi Docker meski symlink public/storage atau Nginx static tidak ada. --}}
        @php $url = route('emptyReturn.photo', $photo); @endphp
        <a href="{{ $url }}" target="_blank" rel="noopener"
           class="block overflow-hidden rounded-lg border border-white/10 hover:border-primary-500 transition">
            <img src="{{ $url }}"
                 alt="Foto botol bekas"
                 class="w-full max-h-[28rem] object-contain bg-black/30">
        </a>
    @empty
        <p class="text-sm text-gray-400 text-center py-6">Pelanggan tidak melampirkan foto.</p>
    @endforelse

    @if ($record->photos->isNotEmpty())
        <p class="text-xs text-gray-400 text-center">Klik foto untuk membuka ukuran penuh di tab baru.</p>
    @endif
</div>

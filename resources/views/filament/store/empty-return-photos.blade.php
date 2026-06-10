<div class="space-y-4">
    @forelse ($record->photos as $photo)
        {{-- URL root-relatif agar cocok dengan host:port yang sedang dipakai
             (hindari APP_URL absolut yang bisa salah port saat `php artisan serve`). --}}
        @php $url = '/storage/'.ltrim($photo->photo_url, '/'); @endphp
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

<x-layouts.public title="Riwayat Pengembalian">
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold" style="font-family:'DM Serif Display',serif">Riwayat Pengembalian</h1>
        <a href="{{ route('emptyReturn.create') }}" class="text-sm px-4 py-2 bg-emerald-600 text-white rounded-full">+ Ajukan</a>
    </div>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif

    @forelse ($returns as $r)
        <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-sm">{{ $r->nama_produk }} <span class="text-gray-400">× {{ $r->jumlah }}</span></p>
                    <p class="text-xs text-gray-400">{{ $r->created_at->format('d M Y') }} · {{ ucfirst($r->metode) }}</p>
                </div>
                @php $b = match($r->status){'approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-red-100 text-red-700','received','picked_up'=>'bg-blue-100 text-blue-700',default=>'bg-amber-100 text-amber-700'}; @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $b }}">{{ ucfirst($r->status) }}</span>
            </div>
            @if ($r->status === 'approved' && $r->poin_earned)
                <p class="text-xs text-emerald-600 mt-2">✓ +{{ $r->poin_earned }} poin dikreditkan</p>
            @endif
            @if ($r->status === 'rejected' && $r->catatan_admin)
                <p class="text-xs text-red-500 mt-2">Alasan: {{ $r->catatan_admin }}</p>
            @endif
        </div>
    @empty
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">♻️</p>
            <p class="mb-4">Belum ada pengajuan.</p>
            <a href="{{ route('emptyReturn.create') }}" class="inline-block px-5 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-full">Ajukan Sekarang</a>
        </div>
    @endforelse

    <div class="mt-6">{{ $returns->links() }}</div>
</div>
</x-layouts.public>

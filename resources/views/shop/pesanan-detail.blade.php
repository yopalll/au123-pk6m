<x-layouts.public title="Detail Pesanan">
@php
    $imgUrl = fn ($url) => $url ? asset(\Illuminate\Support\Str::startsWith($url, 'public/') ? str_replace('public/', 'storage/', $url) : $url) : 'https://placehold.co/120x120/1a1c1f/ffb68b?text=VIYGO';
    $badgeCls = match ($order->status) {
        'pending' => 'bg-amber-100 text-amber-700', 'paid','processing' => 'bg-blue-100 text-blue-700',
        'shipped' => 'bg-indigo-100 text-indigo-700', 'delivered','completed' => 'bg-emerald-100 text-emerald-700',
        'cancelled','refunded' => 'bg-red-100 text-red-700', default => 'bg-gray-100 text-gray-600',
    };
    $canReview = in_array($order->status, ['delivered','completed']);
@endphp
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('shop.pesanan.index') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Semua Pesanan</a>

    @if (session('success'))<div class="my-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif

    <div class="flex items-center justify-between mt-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">Kode Pesanan</p>
            <h1 class="text-xl font-semibold" style="font-family:'DM Serif Display',serif">{{ $order->kode_order }}</h1>
        </div>
        <span class="px-3 py-1.5 rounded-full text-sm font-semibold {{ $badgeCls }}">{{ ucfirst($order->status) }}</span>
    </div>

    {{-- Pembayaran pending --}}
    @if ($order->status === 'pending')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5 flex items-center justify-between">
            <span class="text-sm text-amber-800">Pesanan menunggu pembayaran.</span>
            <a href="{{ route('shop.order.payment', $order->kode_order) }}" class="px-4 py-2 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full">Bayar Sekarang</a>
        </div>
    @endif

    {{-- Resi --}}
    @if ($order->resi)
        <div class="bg-white border border-gray-100 rounded-2xl p-4 mb-5 text-sm">
            <span class="text-gray-500">Resi {{ strtoupper($order->kurir) }} {{ $order->layanan_kirim }}:</span>
            <span class="font-mono font-semibold ml-1">{{ $order->resi }}</span>
        </div>
    @endif

    {{-- Items --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-5">
        <h2 class="font-semibold mb-3">Produk</h2>
        @foreach ($order->items as $item)
            <div class="flex gap-3 py-2 border-b border-gray-50 last:border-0">
                <img src="{{ $imgUrl($item->product?->primaryImage?->image_url) }}" class="w-14 h-14 rounded-lg object-cover shrink-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium line-clamp-1">{{ $item->nama_produk }}</p>
                    <p class="text-xs text-gray-400">{{ $item->qty }} × Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</p>
                </div>
                <span class="text-sm font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
            @if ($canReview && $item->product)
                <div class="pb-2">
                    <button onclick="document.getElementById('rev-{{ $item->id_product }}').classList.toggle('hidden')"
                            class="text-xs text-[#4BA3CC] hover:underline">⭐ Tulis review</button>
                    <form id="rev-{{ $item->id_product }}" method="POST" action="{{ route('shop.produk.review', $item->product->slug) }}"
                          enctype="multipart/form-data" class="hidden mt-2 bg-gray-50 rounded-xl p-3 space-y-2">
                        @csrf
                        <input type="hidden" name="id_product_order" value="{{ $order->id_product_order }}">
                        <select name="rating" required class="text-sm border rounded px-2 py-1">
                            @for ($r=5;$r>=1;$r--)<option value="{{ $r }}">{{ str_repeat('★',$r) }} ({{ $r }})</option>@endfor
                        </select>
                        <input name="judul" placeholder="Judul (opsional)" class="w-full text-sm border rounded px-2 py-1">
                        <textarea name="komentar" placeholder="Ceritakan pengalamanmu…" class="w-full text-sm border rounded px-2 py-1"></textarea>
                        <input type="file" name="foto[]" accept="image/*" multiple class="text-xs">
                        <button class="px-3 py-1.5 bg-[#1B2D6B] text-white text-xs rounded-full">Kirim Review</button>
                    </form>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Alamat --}}
    @if ($order->address)
        <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-5 text-sm">
            <h2 class="font-semibold mb-2">Alamat Pengiriman</h2>
            <p class="font-medium">{{ $order->address->nama_penerima }} · {{ $order->address->phone }}</p>
            <p class="text-gray-500">{{ $order->address->alamat_lengkap }}, {{ $order->address->kota }} {{ $order->address->kode_pos }}</p>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-5 text-sm space-y-1">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Ongkir ({{ strtoupper($order->kurir) }} {{ $order->layanan_kirim }})</span><span>{{ $order->biaya_kirim > 0 ? 'Rp '.number_format($order->biaya_kirim,0,',','.') : 'GRATIS' }}</span></div>
        @if ($order->total_diskon > 0)<div class="flex justify-between text-emerald-600"><span>Diskon</span><span>- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</span></div>@endif
        @if ($order->potongan_poin > 0)<div class="flex justify-between text-emerald-600"><span>Poin ({{ $order->poin_digunakan }})</span><span>- Rp {{ number_format($order->potongan_poin, 0, ',', '.') }}</span></div>@endif
        <div class="flex justify-between font-semibold text-base border-t border-gray-100 pt-2 mt-2"><span>Total</span><span>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span></div>
    </div>

    <a href="{{ route('shop.order.invoice', $order->kode_order) }}"
       class="block text-center py-3 border border-gray-200 text-gray-700 text-sm font-semibold rounded-full hover:border-[#4BA3CC] transition-colors">
        📄 Download Invoice
    </a>
</div>
</x-layouts.public>

<x-layouts.public title="Checkout">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">Checkout</h1>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2">{{ session('error') }}</div>@endif
    @if (session('info'))<div class="mb-4 text-sm text-sky-700 bg-sky-50 rounded-xl px-4 py-2">{{ session('info') }}</div>@endif
    @if ($errors->any())<div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2">{{ $errors->first() }}</div>@endif

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">
        {{-- Kiri: alamat + ongkir --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Alamat --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <h2 class="font-semibold mb-3">📍 Alamat Pengiriman</h2>
                @forelse ($addresses as $addr)
                    <label class="flex gap-3 p-3 rounded-xl border mb-2 cursor-pointer {{ $loop->first ? 'border-[#1B2D6B] bg-[#E8F4FB]/40' : 'border-gray-200' }}">
                        <input type="radio" name="id_address" value="{{ $addr->id_address }}" form="checkout-form" @checked($loop->first) class="mt-1">
                        <div class="text-sm">
                            <span class="font-medium">{{ $addr->label }} · {{ $addr->nama_penerima }}</span>
                            <p class="text-gray-500">{{ $addr->phone }}</p>
                            <p class="text-gray-500">{{ $addr->alamat_lengkap }}, {{ $addr->kota }} {{ $addr->kode_pos }}</p>
                        </div>
                    </label>
                @empty
                    <p class="text-sm text-gray-400 mb-3">Belum ada alamat. Tambahkan di bawah.</p>
                @endforelse

                {{-- Tambah alamat --}}
                <details class="mt-3">
                    <summary class="text-sm text-[#4BA3CC] cursor-pointer">+ Tambah alamat baru</summary>
                    <form method="POST" action="{{ route('shop.address.store') }}" class="grid grid-cols-2 gap-3 mt-3">
                        @csrf
                        <input name="label" placeholder="Label (Rumah/Kantor)" required class="text-sm border rounded-lg px-3 py-2">
                        <input name="nama_penerima" placeholder="Nama penerima" required class="text-sm border rounded-lg px-3 py-2">
                        <input name="phone" placeholder="No. HP" required class="text-sm border rounded-lg px-3 py-2">
                        <input name="kota" placeholder="Kota" required class="text-sm border rounded-lg px-3 py-2">
                        <input name="provinsi" placeholder="Provinsi" class="text-sm border rounded-lg px-3 py-2">
                        <input name="kode_pos" placeholder="Kode pos" required class="text-sm border rounded-lg px-3 py-2">
                        <textarea name="alamat_lengkap" placeholder="Alamat lengkap" required class="col-span-2 text-sm border rounded-lg px-3 py-2"></textarea>
                        <button class="col-span-2 py-2 bg-[#1B2D6B] text-white text-sm rounded-lg">Simpan Alamat</button>
                    </form>
                </details>
            </div>

            {{-- Pengiriman (ongkir flat — tanpa pilih kurir) --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <h2 class="font-semibold mb-3">📦 Pengiriman</h2>
                @if ($freeOngkir)
                    <div class="flex items-center justify-between p-3 rounded-xl border border-emerald-200 bg-emerald-50">
                        <span class="text-sm">
                            <span class="font-medium text-emerald-700">🎉 Gratis Ongkir</span>
                            <span class="text-gray-500 text-xs block">Belanja ≥ Rp {{ number_format($threshold,0,',','.') }} · estimasi {{ config('ongkir.etd','2-4 hari') }}</span>
                        </span>
                        <span class="text-sm font-semibold text-emerald-700">GRATIS</span>
                    </div>
                @else
                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200">
                        <span class="text-sm">
                            <span class="font-medium">Ongkir Flat</span>
                            <span class="text-gray-500 text-xs block">Ke seluruh Indonesia · estimasi {{ config('ongkir.etd','2-4 hari') }}</span>
                        </span>
                        <span class="text-sm font-semibold">Rp {{ number_format($biayaKirim,0,',','.') }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">Belanja lagi Rp {{ number_format(max(0,$threshold - $subtotal),0,',','.') }} untuk gratis ongkir.</p>
                @endif
            </div>
        </div>

        {{-- Kanan: ringkasan --}}
        <div class="mt-6 lg:mt-0">
            <form id="checkout-form" method="POST" action="{{ route('shop.checkout.store') }}"
                  class="bg-white border border-gray-100 rounded-2xl p-5 lg:sticky lg:top-24">
                @csrf

                <h2 class="font-semibold mb-4">Ringkasan</h2>

                <div class="space-y-2 text-sm border-b border-gray-100 pb-3 mb-3">
                    @foreach ($cartItems as $item)
                        <div class="flex justify-between">
                            <span class="text-gray-500 truncate pr-2">{{ $item->product->nama }} ×{{ $item->qty }}</span>
                            <span>Rp {{ number_format($item->product->harga * $item->qty, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Promo --}}
                <input name="promo_code" placeholder="Kode promo (opsional)" class="w-full text-sm border rounded-lg px-3 py-2 mb-2">

                {{-- Poin --}}
                @if ($userPoint && $userPoint->saldo > 0)
                    <div class="bg-amber-50 rounded-lg p-3 mb-3 text-sm">
                        <p class="font-medium text-amber-800">💰 Poin (saldo {{ $userPoint->saldo }})</p>
                        <input type="number" name="poin_digunakan" min="0" max="{{ $userPoint->saldo }}" value="0"
                               class="w-24 mt-1 border rounded px-2 py-1 text-sm"> <span class="text-xs text-amber-700">1 poin = Rp 1.000</span>
                    </div>
                @endif

                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Ongkir</span>
                        <span>{{ $freeOngkir ? 'GRATIS' : 'Rp '.number_format($biayaKirim, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-semibold text-base border-t border-gray-100 pt-2 mt-2">
                        <span>Total</span>
                        <span>Rp {{ number_format($subtotal + $biayaKirim, 0, ',', '.') }}</span>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-4 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                    Buat Pesanan & Bayar
                </button>
                <p class="text-xs text-gray-400 text-center mt-2">Promo & poin dihitung saat pesanan dibuat.</p>
            </form>
        </div>
    </div>
</div>
</x-layouts.public>

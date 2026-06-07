<x-layouts.public title="Checkout">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8"
     x-data="checkout()">
    <h1 class="text-2xl font-semibold mb-6" style="font-family:'DM Serif Display',serif">Checkout</h1>

    @if (session('success'))<div class="mb-4 text-sm text-emerald-600 bg-emerald-50 rounded-xl px-4 py-2">{{ session('success') }}</div>@endif
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

            {{-- Ongkir --}}
            <div class="bg-white border border-gray-100 rounded-2xl p-5">
                <h2 class="font-semibold mb-3">📦 Pengiriman</h2>
                <div class="flex gap-2 mb-3">
                    <input x-model="destination" placeholder="Kota tujuan (mis. Surabaya)"
                           class="flex-1 text-sm border rounded-lg px-3 py-2 outline-none focus:border-[#4BA3CC]">
                    <button type="button" @click="cekOngkir()" :disabled="loading"
                            class="px-4 py-2 bg-[#1B2D6B] text-white text-sm rounded-lg" x-text="loading ? '...' : 'Cek'"></button>
                </div>
                <template x-if="freeOngkir">
                    <p class="text-sm text-emerald-600 mb-2">🎉 Belanja ≥ Rp {{ number_format($threshold,0,',','.') }} — gratis ongkir!</p>
                </template>
                <div class="space-y-2">
                    <template x-for="svc in services" :key="svc.key">
                        <label class="flex items-center justify-between p-3 rounded-xl border cursor-pointer"
                               :class="selected===svc.key ? 'border-[#1B2D6B] bg-[#E8F4FB]/40' : 'border-gray-200'">
                            <span class="flex items-center gap-2 text-sm">
                                <input type="radio" name="svc" :value="svc.key" x-model="selected" @change="pick(svc)">
                                <span x-text="svc.label"></span>
                                <span class="text-gray-400 text-xs" x-text="svc.etd"></span>
                            </span>
                            <span class="text-sm font-semibold" x-text="freeOngkir ? 'GRATIS' : ('Rp ' + svc.cost.toLocaleString('id-ID'))"></span>
                        </label>
                    </template>
                </div>
                <p x-show="services.length===0 && checked" class="text-sm text-gray-400">Masukkan kota lalu klik Cek.</p>
            </div>
        </div>

        {{-- Kanan: ringkasan --}}
        <div class="mt-6 lg:mt-0">
            <form id="checkout-form" method="POST" action="{{ route('shop.checkout.store') }}"
                  class="bg-white border border-gray-100 rounded-2xl p-5 lg:sticky lg:top-24">
                @csrf
                <input type="hidden" name="kurir" :value="kurir">
                <input type="hidden" name="layanan_kirim" :value="layanan">
                <input type="hidden" name="biaya_kirim" :value="freeOngkir ? 0 : biaya">
                <input type="hidden" name="estimasi_tiba" :value="etd">

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
                    <div class="flex justify-between"><span class="text-gray-500">Ongkir</span><span x-text="freeOngkir ? 'GRATIS' : ('Rp ' + biaya.toLocaleString('id-ID'))"></span></div>
                    <div class="flex justify-between font-semibold text-base border-t border-gray-100 pt-2 mt-2">
                        <span>Total</span>
                        <span x-text="'Rp ' + ({{ $subtotal }} + (freeOngkir?0:biaya)).toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <button type="submit" :disabled="!selected && !freeOngkir"
                        class="w-full mt-4 py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors disabled:opacity-50">
                    Buat Pesanan & Bayar
                </button>
                <p class="text-xs text-gray-400 text-center mt-2" x-show="!selected && !freeOngkir">Pilih layanan pengiriman dulu</p>
            </form>
        </div>
    </div>
</div>

<x-slot:scripts>
<script>
function checkout() {
    return {
        destination: '', loading: false, checked: false,
        services: [], selected: '', biaya: 0, kurir: '', layanan: '', etd: '',
        freeOngkir: {{ $subtotal >= $threshold ? 'true' : 'false' }},
        weight: {{ $totalBerat }},
        async cekOngkir() {
            if (!this.destination) return;
            this.loading = true; this.checked = true;
            try {
                const res = await fetch('{{ route('shop.ongkir.check') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ destination: this.destination, weight: this.weight })
                });
                const data = await res.json();
                this.services = [];
                (data.data || []).forEach(c => (c.services || []).forEach(s => {
                    this.services.push({ key: c.courier + '-' + s.service, label: (c.courier_name||c.courier).toUpperCase() + ' ' + s.service, cost: s.cost, etd: s.etd, courier: c.courier, service: s.service });
                }));
            } catch (e) { alert('Gagal cek ongkir'); }
            this.loading = false;
        },
        pick(s) { this.biaya = s.cost; this.kurir = s.courier; this.layanan = s.service; this.etd = s.etd; }
    };
}
</script>
</x-slot:scripts>
</x-layouts.public>

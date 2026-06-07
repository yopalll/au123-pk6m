<x-layouts.public title="Ajukan Pengembalian">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8" x-data="{ metode: 'dropoff', jumlah: 1 }">
    <a href="{{ route('emptyReturn.index') }}" class="text-sm text-gray-400 hover:text-emerald-600">← Empty Return</a>
    <h1 class="text-2xl font-semibold mt-3 mb-6" style="font-family:'DM Serif Display',serif">Ajukan Pengembalian Botol</h1>

    @if ($errors->any())<div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('emptyReturn.store') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-1">Produk</label>
            <input list="produk-list" name="nama_produk" value="{{ old('nama_produk') }}" required placeholder="Nama produk skincare"
                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-emerald-500">
            <datalist id="produk-list">
                @foreach ($purchasedProducts as $p)<option value="{{ $p->nama }}">@endforeach
            </datalist>
            <p class="text-xs text-gray-400 mt-1">Bisa pilih dari riwayat belanja atau ketik manual.</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Jumlah botol</label>
            <input type="number" name="jumlah" x-model.number="jumlah" min="1" max="50" value="1" required
                   class="w-32 text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-emerald-500">
            <p class="text-xs text-emerald-600 mt-1">Estimasi poin: <strong x-text="jumlah * 5"></strong> poin (±5 poin/botol, final ditentukan admin)</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Metode pengembalian</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="p-3 rounded-xl border cursor-pointer text-sm has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                    <input type="radio" name="metode" value="dropoff" x-model="metode" checked> Drop-off di Salon
                </label>
                <label class="p-3 rounded-xl border cursor-pointer text-sm has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                    <input type="radio" name="metode" value="pickup" x-model="metode"> Pickup (alamat)
                </label>
            </div>
        </div>

        <div x-show="metode==='dropoff'">
            <label class="block text-sm font-medium mb-1">Pilih Salon</label>
            <select name="id_salon" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-emerald-500">
                <option value="">— pilih salon —</option>
                @foreach ($salons as $s)<option value="{{ $s->id_salon }}">{{ $s->nama_salon }} — {{ Str::limit($s->alamat, 40) }}</option>@endforeach
            </select>
        </div>

        <div x-show="metode==='pickup'" x-cloak>
            <label class="block text-sm font-medium mb-1">Alamat Pickup</label>
            <textarea name="alamat_pickup" rows="2" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-emerald-500"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Foto botol kosong (maks 3)</label>
            <input type="file" name="foto[]" accept="image/*" multiple
                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 file:mr-3 file:px-3 file:py-1.5 file:rounded-full file:border-0 file:bg-emerald-100 file:text-emerald-700">
        </div>

        <button class="w-full py-3 bg-emerald-600 text-white text-sm font-semibold rounded-full hover:bg-emerald-700 transition-colors">Kirim Pengajuan</button>
    </form>
</div>
</x-layouts.public>

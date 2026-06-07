<x-layouts.public title="Buat Thread">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
    <a href="{{ route('komunitas.index') }}" class="text-sm text-gray-400 hover:text-[#1B2D6B]">← Komunitas</a>
    <h1 class="text-2xl font-semibold mt-3 mb-6" style="font-family:'DM Serif Display',serif">Buat Thread Baru</h1>

    @if ($errors->any())<div class="mb-4 text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('komunitas.thread.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Kategori</label>
            <select name="id_forum_category" required class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-[#4BA3CC]">
                <option value="">— pilih —</option>
                @foreach ($categories as $c)<option value="{{ $c->id_forum_category }}" @selected(old('id_forum_category')==$c->id_forum_category)>{{ $c->icon }} {{ $c->nama }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Judul</label>
            <input name="judul" value="{{ old('judul') }}" required minlength="10" maxlength="255" placeholder="Judul thread (min 10 karakter)"
                   class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-[#4BA3CC]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Konten</label>
            <textarea name="konten" rows="8" required minlength="20" placeholder="Tulis isi thread… (boleh format dasar)"
                      class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2.5 outline-none focus:border-[#4BA3CC]">{{ old('konten') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Tag HTML berbahaya otomatis dibersihkan.</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Tag produk (opsional)</label>
            <select name="product_ids[]" multiple size="5" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 outline-none focus:border-[#4BA3CC]">
                @foreach ($products as $p)<option value="{{ $p->id_product }}">{{ $p->nama }}</option>@endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Ctrl/Cmd + klik untuk pilih beberapa.</p>
        </div>
        <button class="w-full py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">Publikasikan Thread (+5 poin)</button>
    </form>
</div>
</x-layouts.public>

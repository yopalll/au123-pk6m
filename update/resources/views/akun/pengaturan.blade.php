<x-layouts.public title="Pengaturan Akun">
<div class="max-w-2xl mx-auto px-6 py-10">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.index') }}" class="text-[#4BA3CC] hover:underline text-sm">← Akun</a>
        <h1 class="text-2xl text-[#1B2D6B]">Pengaturan Akun</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('akun.pengaturan.update') }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-gray-900 border-b border-gray-100 pb-3">Data Diri</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('name') border-red-400 @enderror" />
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('email') border-red-400 @enderror" />
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit"
                class="px-8 py-3 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
            Simpan Perubahan
        </button>
    </form>
</div>
</x-layouts.public>

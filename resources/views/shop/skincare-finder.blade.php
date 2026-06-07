<x-layouts.public title="Skincare Finder">
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-10"
     x-data="{ step: 1, skin_type: '', concerns: [], looking: '' }">

    <div class="text-center mb-8">
        <p class="text-xs uppercase tracking-widest text-[#4BA3CC] font-semibold">Skincare Finder</p>
        <h1 class="text-3xl font-bold mt-2" style="font-family:'DM Serif Display',serif">Temukan Produk untuk Kulitmu</h1>
    </div>

    {{-- Progress --}}
    <div class="flex items-center gap-2 mb-8">
        <template x-for="i in 3">
            <div class="flex-1 h-1.5 rounded-full" :class="step >= i ? 'bg-[#1B2D6B]' : 'bg-gray-200'"></div>
        </template>
    </div>

    <form method="POST" action="{{ route('shop.skincareFinder.result') }}">
        @csrf
        <input type="hidden" name="skin_type" :value="skin_type">
        <input type="hidden" name="skin_concern" :value="concerns.join(',')">
        <input type="hidden" name="looking_for" :value="looking">

        {{-- Step 1: skin type --}}
        <div x-show="step === 1">
            <h2 class="text-lg font-semibold mb-4">1. Apa tipe kulitmu?</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['oily'=>'Berminyak','dry'=>'Kering','combination'=>'Kombinasi','sensitive'=>'Sensitif','normal'=>'Normal'] as $k=>$v)
                    <button type="button" @click="skin_type='{{ $k }}'; step=2"
                            class="p-4 rounded-2xl border text-sm font-medium transition-colors"
                            :class="skin_type==='{{ $k }}' ? 'border-[#1B2D6B] bg-[#E8F4FB] text-[#1B2D6B]' : 'border-gray-200 hover:border-[#4BA3CC]'">
                        {{ $v }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Step 2: concern --}}
        <div x-show="step === 2" x-cloak>
            <h2 class="text-lg font-semibold mb-4">2. Apa masalah kulit utamamu? <span class="text-xs text-gray-400">(bisa lebih dari 1)</span></h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['dehydration'=>'Dehidrasi','fine_lines'=>'Garis Halus','dullness'=>'Kusam','acne'=>'Jerawat','uneven_skin_tone'=>'Warna Tidak Merata','pores'=>'Pori Besar','dark_circles'=>'Lingkaran Hitam','firmness'=>'Kekencangan'] as $k=>$v)
                    <button type="button" @click="concerns.includes('{{ $k }}') ? concerns=concerns.filter(c=>c!=='{{ $k }}') : concerns.push('{{ $k }}')"
                            class="p-3 rounded-2xl border text-sm font-medium transition-colors"
                            :class="concerns.includes('{{ $k }}') ? 'border-[#1B2D6B] bg-[#E8F4FB] text-[#1B2D6B]' : 'border-gray-200 hover:border-[#4BA3CC]'">
                        {{ $v }}
                    </button>
                @endforeach
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" @click="step=1" class="px-5 py-2.5 border border-gray-200 rounded-full text-sm">← Kembali</button>
                <button type="button" @click="if(concerns.length) step=3" class="flex-1 py-2.5 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold" :class="concerns.length ? '' : 'opacity-50 cursor-not-allowed'">Lanjut →</button>
            </div>
        </div>

        {{-- Step 3: looking for --}}
        <div x-show="step === 3" x-cloak>
            <h2 class="text-lg font-semibold mb-4">3. Apa yang kamu cari?</h2>
            <div class="space-y-3">
                @foreach (['routine'=>'Skincare routine baru','specific'=>'Produk tertentu','bestseller'=>'Rekomendasi best seller'] as $k=>$v)
                    <button type="button" @click="looking='{{ $k }}'"
                            class="w-full p-4 rounded-2xl border text-sm font-medium text-left transition-colors"
                            :class="looking==='{{ $k }}' ? 'border-[#1B2D6B] bg-[#E8F4FB] text-[#1B2D6B]' : 'border-gray-200 hover:border-[#4BA3CC]'">
                        {{ $v }}
                    </button>
                @endforeach
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" @click="step=2" class="px-5 py-2.5 border border-gray-200 rounded-full text-sm">← Kembali</button>
                <button type="submit" class="flex-1 py-2.5 bg-[#1B2D6B] text-white rounded-full text-sm font-semibold">Lihat Rekomendasi ✨</button>
            </div>
        </div>
    </form>
</div>
</x-layouts.public>

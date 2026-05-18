{{-- Komponen: Footer VIYGO --}}
<footer class="bg-[#0F1D4A] text-white mt-16">

    <div class="max-w-7xl mx-auto px-6 py-12">

        {{-- Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-10">

            {{-- Brand --}}
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-[#4BA3CC] flex items-center justify-center font-bold text-white text-lg"
                         style="font-family:'DM Serif Display',serif">V</div>
                    <span class="text-xl font-bold" style="font-family:'DM Serif Display',serif">VIYGO</span>
                </div>
                <p class="text-sm text-white/50 leading-relaxed max-w-xs">
                    Platform library salon terpercaya di Indonesia.
                    Temukan, booking, dan nikmati layanan terbaik.
                </p>
                <div class="flex gap-3 mt-4">
                    @foreach(['instagram','facebook','tiktok'] as $soc)
                    <a href="#" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#4BA3CC] flex items-center justify-center transition-colors">
                        <span class="text-xs font-bold uppercase">{{ substr($soc,0,2) }}</span>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Layanan --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Layanan</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach(['Rambut','Kuku','Alis & Wajah','Pijat & Relaksasi','Makeup','Facial'] as $l)
                        <li><a href="{{ route('kategori.show', strtolower($l)) }}" class="hover:text-[#4BA3CC] transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Perusahaan --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Perusahaan</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach(['Tentang Kami','Karir','Blog','Press','Mitra Salon'] as $l)
                        <li><a href="#" class="hover:text-[#4BA3CC] transition-colors">{{ $l }}</a></li>
                    @endforeach
                    <li><a href="{{ route('mitra') }}" class="hover:text-[#4BA3CC] transition-colors">Daftarkan Salon</a></li>
                </ul>
            </div>

            {{-- Bantuan --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Bantuan</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach(['Pusat Bantuan','Hubungi Kami','Kebijakan Privasi','Syarat & Ketentuan','Cookie Policy'] as $l)
                        <li><a href="#" class="hover:text-[#4BA3CC] transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Bottom --}}
        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/30">
            <span>© {{ date('Y') }} VIYGO. Hak cipta dilindungi undang-undang.</span>
            <span>Made with ♥ in Indonesia</span>
        </div>
    </div>
</footer>

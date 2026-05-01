{{-- Component: VIYGO footer --}}
<footer class="bg-[#0F1D4A] text-white">

    <div class="max-w-7xl mx-auto px-6 py-12">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-10">

            {{-- Brand --}}
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ asset('icon.png') }}?v=3"
                         alt="VIYGO icon"
                         class="h-8 w-8 object-contain flex-shrink-0"
                         onerror="this.style.display='none'" />
                    <img src="{{ asset('white.png') }}?v=3"
                         alt="VIYGO"
                         class="h-7 object-contain"
                         onerror="this.style.display='none'" />
                </div>
                <p class="text-sm text-white/50 leading-relaxed max-w-xs">
                    The trusted beauty marketplace.
                    Discover, book and enjoy the best treatments near you.
                </p>
                <div class="flex gap-3 mt-4">
                    @foreach(['instagram','facebook','tiktok'] as $soc)
                    <a href="#" class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#4BA3CC] flex items-center justify-center transition-colors">
                        <span class="text-xs font-bold uppercase">{{ substr($soc,0,2) }}</span>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Treatments --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Treatments</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach([
                        'Hair'    => 'hair',
                        'Nails'   => 'nail',
                        'Brows'   => 'brow',
                        'Massage' => 'massage',
                        'Makeup'  => 'makeup',
                        'Facial'  => 'facial',
                    ] as $label => $q)
                        <li><a href="{{ route('cari', ['q' => $q]) }}" class="hover:text-[#4BA3CC] transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Company</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach(['About Us','Careers','Blog','Press'] as $l)
                        <li><a href="#" class="hover:text-[#4BA3CC] transition-colors">{{ $l }}</a></li>
                    @endforeach
                    <li><a href="{{ route('mitra') }}" class="hover:text-[#4BA3CC] transition-colors">List your salon</a></li>
                </ul>
            </div>

            {{-- Help --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Help</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    @foreach(['Help Centre','Contact Us','Privacy Policy','Terms & Conditions','Cookie Policy'] as $l)
                        <li><a href="#" class="hover:text-[#4BA3CC] transition-colors">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/30">
            <span>© {{ date('Y') }} VIYGO. All rights reserved.</span>
            <span>Built with ♥ for beauty lovers</span>
        </div>
    </div>
</footer>

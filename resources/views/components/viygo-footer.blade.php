{{-- Component: VIYGO footer --}}
<footer class="bg-[#0c0e11] text-[#e2e2e6] border-t border-white/10">

    <div class="max-w-7xl mx-auto px-6 py-12">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-10">

            {{-- Brand --}}
            <div class="col-span-2 md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ asset('icon.png') }}?v=3"
                         alt="VIYGO icon"
                         class="h-8 w-8 object-contain shrink-0"
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
                    @foreach (config('viygo.social', []) as $soc => $url)
                        <a href="{{ $url ?: '#' }}"
                           target="_blank" rel="noopener"
                           aria-label="VIYGO on {{ ucfirst($soc) }}"
                           class="w-8 h-8 rounded-full bg-white/10 hover:bg-[#4BA3CC] flex items-center justify-center transition-colors">
                            <span class="text-xs font-bold uppercase">{{ substr($soc, 0, 2) }}</span>
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
                    <li><a href="{{ route('static.about') }}"   class="hover:text-[#4BA3CC] transition-colors">About Us</a></li>
                    <li><a href="{{ route('static.careers') }}" class="hover:text-[#4BA3CC] transition-colors">Careers</a></li>
                    <li><a href="{{ route('static.press') }}"   class="hover:text-[#4BA3CC] transition-colors">Press</a></li>
                    <li><a href="{{ route('mitra') }}"          class="hover:text-[#4BA3CC] transition-colors">List your salon</a></li>
                </ul>
            </div>

            {{-- Help --}}
            <div>
                <h4 class="text-sm font-semibold mb-4 uppercase tracking-wider">Help</h4>
                <ul class="space-y-2 text-sm text-white/50">
                    <li><a href="{{ route('static.help') }}"    class="hover:text-[#4BA3CC] transition-colors">Help Centre</a></li>
                    <li><a href="{{ route('static.contact') }}" class="hover:text-[#4BA3CC] transition-colors">Contact Us</a></li>
                    <li><a href="{{ route('static.privacy') }}" class="hover:text-[#4BA3CC] transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('static.terms') }}"   class="hover:text-[#4BA3CC] transition-colors">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('static.cookies') }}" class="hover:text-[#4BA3CC] transition-colors">Cookie Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/30">
            <span>© {{ date('Y') }} VIYGO. All rights reserved.</span>
            <span>Built with ♥ for beauty lovers</span>
        </div>
    </div>
</footer>

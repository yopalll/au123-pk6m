{{--
    Component: VIYGO navbar logo — alternates between light.png and dark.png every 20 s.
    Alpine.js handles the crossfade transition.
    Usage: <x-viygo-logo />
--}}

<a href="{{ route('home') }}"
   class="shrink-0 flex items-center"
   aria-label="VIYGO — Home"
   x-data="{
       show: 0,
       start() {
           setInterval(() => { this.show = this.show === 0 ? 1 : 0; }, 20000);
       }
   }"
   x-init="start()">

    {{-- Crossfade wrapper — fixed size so layout never jumps --}}
    <div class="relative h-8" style="width:130px;">

        {{-- light.png (logo variant 1) --}}
        <img src="{{ asset('light.png') }}?v=3"
             alt="VIYGO"
             class="absolute inset-0 h-8 w-auto max-w-full object-contain object-left transition-opacity duration-700"
             :class="show === 0 ? 'opacity-100' : 'opacity-0'"
             onerror="this.style.display='none'" />

        {{-- dark.png (logo variant 2) --}}
        <img src="{{ asset('dark.png') }}?v=3"
             alt="VIYGO"
             class="absolute inset-0 h-8 w-auto max-w-full object-contain object-left transition-opacity duration-700"
             :class="show === 1 ? 'opacity-100' : 'opacity-0'"
             onerror="this.style.display='none'" />

    </div>

</a>

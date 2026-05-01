{{--
    Component: VIYGO logo
    Props:
      - $dark (bool, default false) — set true when logo is on a dark background.
        dark=false → icon.png + dark.png  (for light navbar)
        dark=true  → icon.png + white.png (for dark footer / dark sections)
    Usage:
      <x-viygo-logo />               ← light background (navbar)
      <x-viygo-logo :dark="true" />  ← dark background (footer)
--}}

@props(['dark' => false])

<a href="{{ route('home') }}"
   class="flex-shrink-0 flex items-center gap-2"
   aria-label="VIYGO — Home">

    {{-- Square icon mark (used in both variants) --}}
    <img src="{{ asset('icon.png') }}?v=2"
         alt=""
         class="h-8 w-8 object-contain flex-shrink-0"
         onerror="this.style.display='none'" />

    {{-- Wordmark: dark on light background --}}
    @if (!$dark)
        <img src="{{ asset('dark.png') }}?v=2"
             alt="VIYGO"
             class="h-6 object-contain"
             onerror="this.style.display='none'" />
    @else
        {{-- Wordmark: white on dark background --}}
        <img src="{{ asset('white.png') }}?v=2"
             alt="VIYGO"
             class="h-6 object-contain"
             onerror="this.style.display='none'" />
    @endif

</a>

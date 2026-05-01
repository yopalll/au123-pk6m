{{--
    Component: VIYGO logo — icon.png + wordmark.
    Usage: <x-viygo-logo />
--}}

<a href="{{ route('home') }}"
   class="flex-shrink-0 flex items-center gap-2"
   aria-label="VIYGO — Home">

    <img src="{{ asset('icon.png') }}?v=2"
         alt="VIYGO"
         class="h-8 w-8 object-contain"
         onerror="this.style.display='none'" />

    <span class="text-xl font-bold text-[#1B2D6B] leading-none"
          style="font-family:'DM Serif Display',serif">VIYGO</span>
</a>

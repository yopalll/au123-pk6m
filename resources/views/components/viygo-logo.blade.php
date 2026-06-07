{{--
    Component: VIYGO wordmark — Serene Floral Noir.
    Playfair Display serif dengan gradasi copper→cream.
    Usage: <x-viygo-logo />
--}}

<a href="{{ route('home') }}"
   class="shrink-0 flex items-center"
   aria-label="VIYGO — Home">
    <span class="viygo-wordmark select-none">VIYGO</span>
</a>

@once
    @push('head')
    @endpush
    <style>
        .viygo-wordmark {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 26px;
            line-height: 1;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, #ffdbc8 0%, #ffb68b 60%, #e09a6a 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
        }
    </style>
@endonce

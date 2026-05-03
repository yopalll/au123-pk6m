<x-layouts.public title="Contact Us">

<section class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Talk to us</div>
    <h1 class="text-4xl text-white mb-4">Contact VIYGO</h1>
    <p class="text-white/60 text-lg max-w-xl mx-auto">
        Email us anytime at
        <a href="mailto:{{ $supportEmail }}" class="text-white underline">{{ $supportEmail }}</a>
        — we reply within one business day.
    </p>
</section>

<div class="max-w-3xl mx-auto px-6 py-12 grid md:grid-cols-2 gap-8">

    {{-- Channels --}}
    <div class="space-y-5">
        <div>
            <h3 class="text-lg text-[#1B2D6B] mb-1">📧 Customer support</h3>
            <a href="mailto:{{ $supportEmail }}" class="text-[#4BA3CC] hover:underline text-sm">{{ $supportEmail }}</a>
        </div>
        <div>
            <h3 class="text-lg text-[#1B2D6B] mb-1">🤝 Salon partners</h3>
            <a href="mailto:partners@viygo.com" class="text-[#4BA3CC] hover:underline text-sm">partners@viygo.com</a>
        </div>
        <div>
            <h3 class="text-lg text-[#1B2D6B] mb-1">📰 Press &amp; media</h3>
            <a href="mailto:press@viygo.com" class="text-[#4BA3CC] hover:underline text-sm">press@viygo.com</a>
        </div>
        <div>
            <h3 class="text-lg text-[#1B2D6B] mb-1">📍 Office</h3>
            <p class="text-sm text-gray-600">VIYGO Ltd · London, United Kingdom</p>
        </div>
    </div>

    {{-- Form (front-end only — submission target TBD) --}}
    <form method="POST" action="#" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
        @csrf
        <h3 class="font-semibold text-[#1B2D6B]">Send us a message</h3>
        <input name="name" required placeholder="Your name"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC]" />
        <input type="email" name="email" required placeholder="Your email"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC]" />
        <textarea name="message" rows="4" required placeholder="How can we help?"
                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] resize-none"></textarea>
        <button type="submit"
                class="w-full py-3 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
            Send message
        </button>
        <p class="text-xs text-gray-400 text-center">
            Or just email us directly at <a href="mailto:{{ $supportEmail }}" class="text-[#4BA3CC] hover:underline">{{ $supportEmail }}</a>.
        </p>
    </form>
</div>

</x-layouts.public>

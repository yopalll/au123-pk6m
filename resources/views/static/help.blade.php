<x-layouts.public title="Help Centre">

<section class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">We're here to help</div>
    <h1 class="text-4xl text-white mb-4">Help Centre</h1>
    <p class="text-white/60 text-lg max-w-xl mx-auto">
        Quick answers to common questions. Can't find what you need?
        Reach our support team at
        <a href="mailto:{{ $supportEmail }}" class="text-white underline">{{ $supportEmail }}</a>.
    </p>
</section>

<div class="max-w-3xl mx-auto px-6 py-12">

    <h2 class="text-2xl text-[#1B2D6B] mb-6">Frequently asked questions</h2>

    <div class="space-y-3">
        @foreach ([
            ['How do I book a treatment?', 'Browse salons, pick a service, choose a date and time, then confirm. Your booking is created instantly — no payment required upfront.'],
            ['Can I cancel my booking?', 'Yes. From "My Bookings" in your account, click "Cancel" on any pending booking. Cancellations made before the appointment time are always free.'],
            ['How is payment handled?', 'Right now, payment is taken in salon. We\'re rolling out online payments via Midtrans soon — your existing bookings will be unaffected.'],
            ['How do reviews work?', 'Once your booking is marked complete by the salon, you\'ll be able to leave a 1–5 star review and a short comment.'],
            ['I\'m a salon owner. How do I list my salon?', 'Visit our partner page at /mitra and submit the registration form. Our salon success team will reach out within 1–2 business days.'],
            ['Do you operate outside the UK?', 'Today our catalogue is UK-focused (8,750+ salons). International expansion is on the roadmap for 2027.'],
        ] as [$q, $a])
            <details class="border border-gray-100 rounded-xl px-5 py-4 group">
                <summary class="font-semibold text-gray-800 cursor-pointer flex items-center justify-between gap-3">
                    {{ $q }}
                    <span class="text-[#4BA3CC] group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <p class="text-sm text-gray-600 mt-3 leading-relaxed">{{ $a }}</p>
            </details>
        @endforeach
    </div>

    <div class="mt-12 bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-8 text-center">
        <h3 class="text-xl text-[#1B2D6B] mb-2">Still need help?</h3>
        <p class="text-sm text-gray-600 mb-4">
            Our support team responds within one business day.
        </p>
        <a href="mailto:{{ $supportEmail }}"
           class="inline-block px-6 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
            Email {{ $supportEmail }}
        </a>
        <a href="{{ route('static.contact') }}"
           class="inline-block ml-2 px-6 py-3 border border-gray-200 rounded-full font-semibold text-gray-700 hover:border-[#1B2D6B] transition-colors">
            Contact form
        </a>
    </div>
</div>

</x-layouts.public>

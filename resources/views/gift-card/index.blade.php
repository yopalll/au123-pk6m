<x-layouts.public title="VIYGO Gift Card">

{{-- Hero --}}
<div class="relative bg-[#1B2D6B] py-20 overflow-hidden">
    <div class="absolute inset-0 opacity-20"
         style="background:radial-gradient(circle at 20% 50%, #4BA3CC 0%, transparent 50%),
                          radial-gradient(circle at 80% 50%, #4BA3CC 0%, transparent 40%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">The Perfect Present</div>
        <h1 class="text-4xl md:text-5xl text-white mb-4" style="font-family:'DM Serif Display',serif">
            VIYGO Gift Cards
        </h1>
        <p class="text-white/70 text-lg max-w-xl mx-auto mb-8">
            Treat someone you love. Redeemable at every salon, spa and wellness studio on VIYGO.
        </p>
        <a href="#choose"
           class="inline-block px-8 py-3 bg-white text-[#1B2D6B] font-bold rounded-full hover:bg-[#E8F4FB] transition-colors">
            Pick a Gift Card →
        </a>
    </div>
</div>

{{-- How it works --}}
<div class="max-w-5xl mx-auto px-6 py-16">
    <div class="grid md:grid-cols-2 gap-12 items-center">
        <div>
            <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">How it works</span>
            <h2 class="text-3xl text-[#1B2D6B] mt-2 mb-6" style="font-family:'DM Serif Display',serif">
                Send beauty in three taps
            </h2>
            <div class="space-y-5">
                @foreach([
                    ['Pick a value','Choose any amount from £25 to £200 (or set a custom amount up to £500).'],
                    ['Personalise','Add a recipient name, your message, and pick a delivery date — instant or scheduled.'],
                    ['They redeem','Recipient enters the code at checkout — discount applies automatically. Unused balance rolls over.'],
                ] as $i => [$title, $desc])
                    <div class="flex gap-4">
                        <div class="w-9 h-9 rounded-full bg-[#1B2D6B] text-white flex items-center justify-center text-sm font-bold shrink-0">
                            {{ $i + 1 }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ $title }}</div>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $desc }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Sample gift-card visual --}}
        <div class="relative">
            <div class="absolute inset-0 bg-[#4BA3CC]/20 blur-3xl rounded-full"></div>
            <div class="relative bg-linear-to-br from-[#1B2D6B] via-[#2C4189] to-[#4BA3CC] rounded-2xl p-8 text-white shadow-2xl rotate-1 hover:rotate-0 transition-transform">
                <div class="flex items-start justify-between mb-12">
                    <div class="text-3xl font-bold tracking-wide" style="font-family:'DM Serif Display',serif">VIYGO</div>
                    <span class="text-2xl">🎁</span>
                </div>
                <div class="text-xs uppercase tracking-widest text-white/60 mb-1">Gift Card</div>
                <div class="text-4xl font-bold mb-6" style="font-family:'DM Serif Display',serif">£100.00</div>
                <div class="bg-white/15 backdrop-blur rounded-lg px-4 py-2 font-mono text-xs tracking-wider">
                    VYG-AB12-CD34-EF56
                </div>
                <div class="mt-3 text-xs text-white/60">Valid until 31 Dec 2027 · No expiry on balance</div>
            </div>
        </div>
    </div>
</div>

{{-- Choose amount --}}
<div id="choose" class="bg-gray-50 py-16">
    <div class="max-w-5xl mx-auto px-6">
        <div class="text-center mb-10">
            <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Step 1 of 3</span>
            <h2 class="text-3xl text-[#1B2D6B] mt-2" style="font-family:'DM Serif Display',serif">
                Choose an amount
            </h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8" x-data="{ amount: null, custom: '' }">
            @foreach([25, 50, 100, 200] as $nominal)
                <button type="button"
                        @click="amount = {{ $nominal }}; custom = ''"
                        :class="amount === {{ $nominal }} ? 'border-[#1B2D6B] bg-[#1B2D6B] text-white shadow-lg -translate-y-1' : 'border-gray-200 bg-white hover:border-[#1B2D6B] hover:-translate-y-1'"
                        class="border-2 rounded-2xl p-6 text-center transition-all duration-200">
                    <div class="text-2xl font-bold" style="font-family:'DM Serif Display',serif">
                        £{{ number_format($nominal, 2, '.', ',') }}
                    </div>
                </button>
            @endforeach

            {{-- Custom --}}
            <div :class="custom ? 'border-[#1B2D6B] bg-[#1B2D6B] text-white shadow-lg -translate-y-1' : 'border-gray-200 bg-white'"
                 class="border-2 rounded-2xl p-6 text-center transition-all col-span-2 md:col-span-4">
                <div class="text-xs font-semibold uppercase tracking-wider mb-2">Custom amount</div>
                <div class="flex items-center justify-center gap-2">
                    <span class="text-xl">£</span>
                    <input type="number" min="10" max="500" step="1"
                           x-model="custom" @input="amount = null"
                           placeholder="any amount, £10 – £500"
                           class="w-44 bg-transparent border-b border-current text-xl font-bold text-center outline-none placeholder:text-current/50" />
                </div>
            </div>
        </div>

        <div class="text-center">
            <button class="px-10 py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                Continue to Personalise →
            </button>
            <p class="text-xs text-gray-400 mt-3">
                Pay securely with card, Apple Pay, Google Pay, or any e-wallet via Midtrans.
            </p>
        </div>
    </div>
</div>

{{-- Why VIYGO Gift Cards --}}
<div class="max-w-5xl mx-auto px-6 py-16">
    <h2 class="text-3xl text-[#1B2D6B] text-center mb-10" style="font-family:'DM Serif Display',serif">
        Why a VIYGO Gift Card?
    </h2>
    <div class="grid md:grid-cols-3 gap-6">
        @foreach([
            ['🌍','Works at 8,750+ salons',"Recipients aren't locked into a single venue — they pick from the whole VIYGO network."],
            ['📱','Email or print','Send it digitally for an instant gift, or print at home for a tangible card.'],
            ['🕒','Two-year validity','Plenty of time to plan that pamper day. Unused balance rolls over.'],
            ['💼','Corporate options','Bulk orders for clients or staff — get in touch for pricing.'],
            ['🎯','No hidden fees','The full amount goes to the salon. No service fees deducted.'],
            ['↩️','Easy refunds',"Lost the code? Contact support and we'll re-issue it."],
        ] as [$icon, $title, $desc])
            <div class="p-6 rounded-2xl bg-gray-50 border border-gray-100 hover:border-[#C5E1F0] hover:bg-[#E8F4FB]/40 transition-all">
                <div class="text-3xl mb-3">{{ $icon }}</div>
                <h3 class="font-semibold text-gray-900 mb-1">{{ $title }}</h3>
                <p class="text-sm text-gray-500">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</div>

{{-- FAQ --}}
<div class="bg-[#E8F4FB]/40 py-16">
    <div class="max-w-3xl mx-auto px-6">
        <h2 class="text-3xl text-[#1B2D6B] text-center mb-8" style="font-family:'DM Serif Display',serif">
            Frequently asked
        </h2>
        <div class="space-y-3">
            @foreach([
                ['How long is a VIYGO Gift Card valid?','Two years from the purchase date. The unused balance rolls over after redemption.'],
                ['Can I use it at any salon?','Yes — every business listed on VIYGO accepts gift cards. No need to check first.'],
                ['Can the recipient pay extra if their booking costs more?',"Absolutely. The gift card covers part of the bill; they'll pay the difference at checkout."],
                ['What if I lose my gift card code?',"Email support@viygo.com — we'll look up your purchase and re-send the code."],
                ['Is the gift card refundable?',"We can refund within 14 days of purchase if the code hasn't been used yet."],
                ['Do you offer corporate / bulk pricing?','We do — write to <a href="mailto:partners@viygo.com" class="text-[#1B2D6B] underline">partners@viygo.com</a> with the volume you need.'],
            ] as [$q, $a])
                <details class="group bg-white rounded-xl border border-gray-100 overflow-hidden">
                    <summary class="flex items-center justify-between cursor-pointer p-5 list-none">
                        <span class="font-medium text-gray-900">{{ $q }}</span>
                        <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-gray-600 leading-relaxed">{!! $a !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</div>

{{-- CTA --}}
<div class="bg-[#1B2D6B] py-12">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="text-2xl text-white mb-3" style="font-family:'DM Serif Display',serif">
            Give the gift of self-care
        </h2>
        <p class="text-white/70 text-sm mb-6 max-w-md mx-auto">
            From birthdays to "just because" — a VIYGO Gift Card always fits.
        </p>
        <a href="#choose" class="inline-block px-8 py-3 bg-white text-[#1B2D6B] font-bold rounded-full hover:bg-[#E8F4FB] transition-colors">
            Buy a Gift Card →
        </a>
    </div>
</div>

</x-layouts.public>

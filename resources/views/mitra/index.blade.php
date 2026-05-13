<x-layouts.public title="Partner with VIYGO">

{{-- Hero --}}
<div class="relative bg-[#1B2D6B] py-24 overflow-hidden">
    <div class="absolute inset-0 opacity-20"
         style="background:radial-gradient(circle at 20% 50%, #4BA3CC 0%, transparent 45%),
                          radial-gradient(circle at 80% 50%, #C5E1F0 0%, transparent 35%)"></div>
    <div class="relative max-w-5xl mx-auto px-6 text-center">
        <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">For Salon Owners</div>
        <h1 class="text-4xl md:text-6xl text-white mb-5" style="font-family:'DM Serif Display',serif">
            Grow your salon<br />
            <em class="text-[#4BA3CC] not-italic">faster</em> with VIYGO
        </h1>
        <p class="text-white/70 text-lg max-w-xl mx-auto mb-8">
            Join 8,750+ salons getting bookings on autopilot. Free to list, 0% commission for the first 90 days.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="#daftar" class="px-8 py-3.5 bg-white text-[#1B2D6B] font-bold rounded-full hover:bg-[#E8F4FB] transition-colors text-lg">
                List your salon — Free
            </a>
            <a href="#how" class="px-8 py-3.5 border-2 border-white/30 text-white font-semibold rounded-full hover:border-white transition-colors text-lg">
                See how it works
            </a>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="bg-[#E8F4FB] border-y border-[#C5E1F0] py-10">
    <div class="max-w-5xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        @foreach([
            ['8,750+','Partner salons'],
            ['190K+','Treatments listed'],
            ['1.2M+','Monthly searches'],
            ['4.8★','Average rating'],
        ] as [$n, $l])
            <div>
                <div class="text-3xl md:text-4xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $n }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $l }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- How it works --}}
<div id="how" class="max-w-6xl mx-auto px-6 py-20">
    <div class="text-center mb-12">
        <span class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">In three steps</span>
        <h2 class="text-3xl md:text-4xl text-[#1B2D6B] mt-2" style="font-family:'DM Serif Display',serif">
            Live in 10 minutes
        </h2>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        @foreach([
            ['📝','Apply',"Tell us about your salon, location, and services. We'll review and onboard you within 24 hours."],
            ['🛠️','Set up','Add staff, working hours, and treatment menu via the Owner dashboard. Photos in five minutes.'],
            ['📈','Get bookings','Customers find you, book directly, and pay through Midtrans. You manage everything from one place.'],
        ] as $i => [$icon, $title, $desc])
            <div class="relative">
                <div class="text-5xl mb-4">{{ $icon }}</div>
                <div class="text-xs text-[#4BA3CC] font-bold uppercase tracking-wider mb-1">Step {{ $i + 1 }}</div>
                <h3 class="text-xl font-semibold text-[#1B2D6B] mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
            </div>
        @endforeach
    </div>
</div>

{{-- Benefits --}}
<div class="bg-gray-50 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">
                Everything you need to run your salon
            </h2>
            <p class="text-gray-500 text-sm mt-3 max-w-xl mx-auto">
                Marketing, scheduling, payments and analytics — all included, all built for salon owners.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach([
                ['📈','Reach new customers','Get in front of millions of users searching for treatments near them every day.'],
                ['📅','Online booking 24/7','Customers book themselves — even at 2am. No more missed calls or DM requests.'],
                ['💳','Secure payments','Midtrans Snap handles cards, e-wallets, and virtual accounts. Funds settle to your bank weekly.'],
                ['👥','Staff & schedules','Add staff, set working hours, assign services. The booking grid respects every constraint automatically.'],
                ['⭐','Verified reviews','Only customers who actually attended can leave a review — no review-bombing from competitors.'],
                ['📊','Live dashboard',"Track today's bookings, monthly revenue, ratings, and pending approvals at a glance."],
                ['🎁','Gift card sales','Customers buy gift cards on VIYGO and redeem them at your salon. Extra revenue, zero friction.'],
                ['🛡️','No-show protection',"Booking-deposit option holds card details up front so cancellations don't hurt your day."],
                ['📱','Mobile-friendly','The Owner dashboard works just as well on your phone — manage bookings between clients.'],
            ] as [$icon, $title, $desc])
                <div class="bg-white rounded-2xl p-6 border border-gray-100 hover:border-[#C5E1F0] hover:shadow-lg transition-all">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-semibold text-gray-900 mb-1.5">{{ $title }}</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Pricing / commission --}}
<div class="max-w-5xl mx-auto px-6 py-20">
    <div class="text-center mb-10">
        <h2 class="text-3xl md:text-4xl text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">
            Honest, simple pricing
        </h2>
        <p class="text-gray-500 text-sm mt-3">
            No setup fees. No monthly subscriptions. We only earn when you do.
        </p>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-2xl p-7">
            <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Onboarding</div>
            <div class="text-4xl font-bold text-[#1B2D6B] mt-2" style="font-family:'DM Serif Display',serif">£0</div>
            <p class="text-sm text-gray-500 mt-1">Free to apply, free to list. No credit card required.</p>
        </div>
        <div class="bg-[#1B2D6B] text-white border-2 border-[#1B2D6B] rounded-2xl p-7 transform md:scale-105 shadow-2xl relative">
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-[#4BA3CC] text-xs font-bold rounded-full">
                MOST POPULAR
            </div>
            <div class="text-xs font-bold text-[#C5E1F0] uppercase tracking-wider">Per booking</div>
            <div class="text-4xl font-bold mt-2" style="font-family:'DM Serif Display',serif">7%</div>
            <p class="text-sm text-white/70 mt-1">
                Charged only on bookings that actually take place. 0% on cancellations and no-shows.
            </p>
            <div class="mt-4 pt-4 border-t border-white/20 text-xs text-white/70">
                <div class="flex items-center gap-2 mb-1.5"><span class="text-[#4BA3CC]">✓</span> First 90 days free</div>
                <div class="flex items-center gap-2 mb-1.5"><span class="text-[#4BA3CC]">✓</span> Marketing tools included</div>
                <div class="flex items-center gap-2"><span class="text-[#4BA3CC]">✓</span> Cancel any time</div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-7">
            <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-wider">Payment processing</div>
            <div class="text-4xl font-bold text-[#1B2D6B] mt-2" style="font-family:'DM Serif Display',serif">2.9%</div>
            <p class="text-sm text-gray-500 mt-1">Standard Midtrans card processing — same as anywhere else.</p>
        </div>
    </div>
</div>

{{-- Testimonials --}}
<div class="bg-[#E8F4FB]/40 py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">
                Salons that grew with VIYGO
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            @foreach([
                ['"We doubled weekday bookings in our first month. The dashboard alone is worth it — we used to track everything in a spiral notebook."','Sasha Williams','Bond Street Hair Co., London','+98% bookings'],
                ['"Customers love being able to pick a specific stylist. We barely take calls anymore — my receptionist is finally free to actually receive."','Mia Tan','The Polished Studio, Manchester','+62% revenue'],
                ['"Reviews on VIYGO are real customers. That\'s what got me to switch from the older booking platform."','Léa Petit','Glow Atelier, Edinburgh','4.9★ avg rating'],
            ] as [$quote, $name, $where, $stat])
                <figure class="bg-white rounded-2xl p-6 border border-gray-100 flex flex-col">
                    <blockquote class="text-gray-700 leading-relaxed mb-4 flex-1 italic">
                        {{ $quote }}
                    </blockquote>
                    <figcaption class="border-t border-gray-100 pt-4">
                        <div class="font-semibold text-gray-900">{{ $name }}</div>
                        <div class="text-xs text-gray-500">{{ $where }}</div>
                        <div class="text-xs text-[#4BA3CC] font-semibold mt-2">{{ $stat }}</div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</div>

{{-- FAQ --}}
<div class="max-w-3xl mx-auto px-6 py-20">
    <h2 class="text-3xl text-[#1B2D6B] text-center mb-8" style="font-family:'DM Serif Display',serif">
        Frequently asked
    </h2>
    <div class="space-y-3">
        @foreach([
            ['Is there a setup fee?','No. Listing is completely free. The only charge is 7% commission on completed bookings — and the first 90 days are free.'],
            ['How long does onboarding take?',"Usually under 24 hours. Apply via the form below, our team verifies your business, then you're live."],
            ['Can I keep my existing booking system?','Yes. Many salons use VIYGO alongside walk-ins or their own site. We integrate with most calendar tools.'],
            ['When do I get paid?',"Settlement runs every Monday for the previous week's bookings. Funds land in your registered UK bank account."],
            ['What if a customer no-shows?',"You're not charged commission on no-shows. Optional booking deposits give you extra protection."],
            ['Who do I contact for support?','Email <a href="mailto:partners@viygo.com" class="text-[#1B2D6B] underline">partners@viygo.com</a> or message your dedicated account manager once you\'re onboarded.'],
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

{{-- Application form --}}
<div id="daftar" class="bg-[#1B2D6B] py-20">
    <div class="max-w-2xl mx-auto px-6">
        <div class="text-center mb-8">
            <h2 class="text-3xl md:text-4xl text-white mb-3" style="font-family:'DM Serif Display',serif">
                Apply to list your salon
            </h2>
            <p class="text-white/70 text-sm">
                Takes 2 minutes. We'll get back to you within 24 hours.
            </p>
        </div>

        @if (session('mitra_applied'))
            <div class="bg-white rounded-2xl p-8 shadow-2xl text-center">
                <div class="text-5xl mb-4">✓</div>
                <h3 class="text-2xl text-[#1B2D6B] mb-2" style="font-family:'DM Serif Display',serif">
                    Application received
                </h3>
                <p class="text-sm text-gray-600 max-w-md mx-auto">
                    {{ session('success') }}
                </p>
                <a href="{{ route('home') }}" class="inline-block mt-6 text-sm text-[#4BA3CC] hover:underline">
                    Back to homepage
                </a>
            </div>
        @else
            <form action="{{ route('mitra.apply') }}" method="POST" class="bg-white rounded-2xl p-8 shadow-2xl space-y-4">
                @csrf

                @if ($errors->any())
                    <div class="p-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Salon name *</label>
                        <input type="text" name="nama_salon" required placeholder="e.g. The Beauty Lounge"
                               value="{{ old('nama_salon') }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner name *</label>
                        <input type="text" name="nama_pemilik" required
                               value="{{ old('nama_pemilik') }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" required
                               value="{{ old('email') }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <input type="tel" name="phone" required placeholder="07xxx xxx xxx"
                               value="{{ old('phone') }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <select name="kota" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors bg-white">
                        <option value="">Choose a city…</option>
                        @foreach ($kotas ?? [] as $kota)
                            <option value="{{ $kota->id_kota }}" @selected(old('kota') == $kota->id_kota)>{{ $kota->nama_kota }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tell us about your salon</label>
                    <textarea name="catatan" rows="4" placeholder="What treatments do you offer? How many staff? Anything we should know?"
                              class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors resize-none">{{ old('catatan') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors mt-2">
                    Apply now — it's free →
                </button>

                <p class="text-xs text-gray-400 text-center">
                    By applying, you agree to VIYGO's <a href="{{ route('static.terms') }}" class="underline">Terms</a>
                    and <a href="{{ route('static.privacy') }}" class="underline">Privacy Policy</a>.
                </p>
            </form>
        @endif
    </div>
</div>

</x-layouts.public>

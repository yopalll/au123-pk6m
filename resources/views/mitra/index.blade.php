<x-layouts.public title="Partner with VIYGO">
<div class="bg-[#1B2D6B] py-20 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">For Salon Owners</div>
    <h1 class="text-5xl text-white mb-4">Grow Your<br /><em class="text-[#4BA3CC]">Salon</em> with VIYGO</h1>
    <p class="text-white/60 text-lg max-w-xl mx-auto mb-8">
        Join thousands of salons on VIYGO and bring in new customers every single day.
    </p>
    <a href="#daftar" class="px-8 py-3.5 bg-white text-[#1B2D6B] font-bold rounded-full hover:bg-[#E8F4FB] transition-colors text-lg">
        List Your Salon — Free →
    </a>
</div>

{{-- Stats --}}
<div class="bg-[#E8F4FB] border-y border-[#C5E1F0] py-8">
    <div class="max-w-4xl mx-auto px-6 grid grid-cols-3 gap-6 text-center">
        @foreach([['5,700+','Partner Salons'],['190K+','Treatments Listed'],['4.8★','Platform Rating']] as [$n,$l])
            <div>
                <div class="text-3xl font-bold text-[#1B2D6B]" style="font-family:'DM Serif Display',serif">{{ $n }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ $l }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Benefits --}}
<div class="max-w-5xl mx-auto px-6 py-16">
    <h2 class="text-3xl text-[#1B2D6B] text-center mb-10">Why Partner with Us</h2>
    <div class="grid md:grid-cols-3 gap-6 mb-16">
        @foreach([
            ['📈','Reach New Customers',     'Get in front of thousands of active users searching for salons every day.'],
            ['📅','Easy Booking Management', 'Customers book themselves; you just deliver the treatment.'],
            ['💰','No Upfront Fees',         'Joining is free. Commission is only charged on successful bookings.'],
            ['📊','Powerful Analytics',      'Track performance, reviews and revenue from a single dashboard.'],
            ['⭐','Build Your Reputation',   'Collect verified reviews and earn customer trust.'],
            ['📱','Mobile App',              'Manage your salon on the go through the VIYGO Partner app.'],
        ] as [$icon, $title, $desc])
            <div class="text-center p-6 bg-gray-50 rounded-2xl border border-gray-100 hover:border-[#C5E1F0] hover:bg-[#E8F4FB]/40 transition-all">
                <div class="text-4xl mb-4">{{ $icon }}</div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $title }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $desc }}</p>
            </div>
        @endforeach
    </div>

    {{-- Registration form (placeholder — backend not yet implemented) --}}
    <div id="daftar" class="max-w-lg mx-auto bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
        <h2 class="text-2xl text-[#1B2D6B] mb-6 text-center">List Your Salon</h2>
        <form action="#" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salon Name *</label>
                <input type="text" name="nama_salon" required placeholder="e.g. The Beauty Lounge"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Owner Name *</label>
                <input type="text" name="nama_pemilik" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" required
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                <input type="tel" name="phone" required placeholder="07xxx xxx xxx"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                <select name="kota" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors bg-white">
                    <option value="">Choose a city…</option>
                    @foreach ($kotas ?? [] as $kota)
                        <option value="{{ $kota->id_kota }}">{{ $kota->nama_kota }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="w-full py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors mt-2">
                Apply Now — It's Free
            </button>
        </form>
    </div>
</div>
</x-layouts.public>

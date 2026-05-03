<x-layouts.public title="Careers at VIYGO">

<section class="bg-[#1B2D6B] py-20 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Join the team</div>
    <h1 class="text-5xl text-white mb-4">Build the future of beauty with us</h1>
    <p class="text-white/60 text-lg max-w-2xl mx-auto">
        Help us reshape how millions of people discover &amp; book beauty treatments across the UK.
    </p>
</section>

<div class="max-w-5xl mx-auto px-6 py-16 space-y-12">

    {{-- See README-GAMBAR-STATIS.md → img-careers-team --}}
    <div class="aspect-[16/7] rounded-2xl bg-linear-to-br from-[#E8F4FB] to-[#C5E1F0] flex items-center justify-center text-6xl">
        🤝
    </div>

    <h2 class="text-2xl text-[#1B2D6B] text-center">Open positions</h2>

    <div class="space-y-4">
        @foreach ([
            ['Senior Backend Engineer', 'Engineering',  'Remote (UK)',     'Full-time'],
            ['Product Designer',         'Design',       'London',          'Full-time'],
            ['Salon Success Manager',    'Customer',     'Manchester',      'Full-time'],
            ['Marketing Lead',           'Marketing',    'London',          'Full-time'],
            ['Data Analyst',             'Data',         'Remote (UK)',     'Full-time'],
        ] as [$role, $team, $loc, $type])
            <div class="border border-gray-100 rounded-2xl p-6 hover:border-[#4BA3CC] transition-colors flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $role }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $team }} · {{ $loc }} · {{ $type }}</p>
                </div>
                <a href="mailto:{{ config('viygo.support_email') }}?subject=Application: {{ $role }}"
                   class="px-5 py-2 border border-gray-200 rounded-full text-sm font-medium text-gray-700 hover:border-[#1B2D6B] hover:text-[#1B2D6B] transition-colors">
                    Apply →
                </a>
            </div>
        @endforeach
    </div>

    <div class="text-center text-sm text-gray-500">
        Don't see your role? Email us at
        <a href="mailto:{{ config('viygo.support_email') }}" class="text-[#4BA3CC] hover:underline">{{ config('viygo.support_email') }}</a>
        — we'd love to hear from you.
    </div>
</div>

</x-layouts.public>

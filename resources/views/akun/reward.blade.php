<x-layouts.public title="VIYGO Rewards">
<div class="bg-[#1B2D6B] py-16 text-center">
    <div class="text-xs font-bold text-[#4BA3CC] uppercase tracking-widest mb-3">Loyalty Programme</div>
    <h1 class="text-4xl text-white mb-4">VIYGO Rewards</h1>
    <p class="text-white/60 text-lg">Earn points on every booking and redeem them for vouchers</p>
</div>
<div class="max-w-3xl mx-auto px-6 py-12 space-y-12">

    {{-- Loyalty progress (placeholder counter — points engine not yet implemented) --}}
    <div class="bg-[#E8F4FB] border border-[#C5E1F0] rounded-2xl p-8 text-center">
        <div class="text-5xl font-bold text-[#1B2D6B] mb-1">0</div>
        <div class="text-gray-500 text-sm mb-4">Current Points</div>
        <div class="w-full bg-[#C5E1F0] rounded-full h-2 mb-2">
            <div class="bg-[#1B2D6B] h-2 rounded-full" style="width: 0%"></div>
        </div>
        <div class="text-xs text-gray-500">0 / 1,500 points to unlock a £15 voucher</div>
    </div>

    {{-- My vouchers / promos --}}
    <div>
        <h2 class="text-xl text-[#1B2D6B] mb-4">My Vouchers</h2>

        @if (!empty($promos) && $promos->isNotEmpty())
            <div class="space-y-3">
                @foreach ($promos as $promo)
                    @php
                        $expired = $promo->time_expired && $promo->time_expired->isPast();
                        $used    = (bool) ($promo->pivot->is_used ?? false);
                    @endphp
                    <div class="flex items-center justify-between gap-4 p-5 rounded-2xl border
                                {{ $used || $expired ? 'border-gray-100 bg-gray-50 opacity-70' : 'border-[#C5E1F0] bg-white' }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-900">{{ $promo->nama_promo }}</span>
                                @if ($used)
                                    <span class="px-2 py-0.5 bg-gray-200 text-gray-600 text-xs rounded-full">Used</span>
                                @elseif ($expired)
                                    <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full">Expired</span>
                                @else
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs rounded-full">Active</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 mb-1">{{ $promo->deskripsi_promo }}</p>
                            <div class="flex items-center gap-3 text-xs text-gray-400">
                                @if ($promo->kode_promo)
                                    <span class="font-mono bg-[#E8F4FB] text-[#1B2D6B] px-2 py-0.5 rounded">{{ $promo->kode_promo }}</span>
                                @endif
                                @if ($promo->time_expired)
                                    <span>Expires {{ $promo->time_expired->isoFormat('D MMM Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <div class="text-2xl font-bold text-[#1B2D6B]">
                                @if ($promo->tipe_promo === 'percent')
                                    {{ rtrim(rtrim(number_format($promo->diskon, 2, '.', ''), '0'), '.') }}%
                                @else
                                    £{{ number_format($promo->diskon, 2, '.', ',') }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-400">off</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center p-8 border border-dashed border-gray-200 rounded-2xl text-gray-400 text-sm">
                You don't have any vouchers yet. Earn points on bookings and reviews to unlock rewards.
            </div>
        @endif
    </div>

    {{-- How to earn --}}
    <div>
        <h2 class="text-xl text-[#1B2D6B] mb-6">How to Earn Points</h2>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach([
                ['📅','Book a Treatment','Earn 10 points for every £10 you spend on a booking'],
                ['⭐','Leave a Review',     'Earn 50 points each time you review a salon'],
                ['👥','Invite a Friend',    'Earn 200 points for every friend who joins VIYGO'],
            ] as [$i,$t,$d])
                <div class="text-center p-5 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="text-3xl mb-3">{{ $i }}</div>
                    <div class="font-semibold text-gray-900 mb-1">{{ $t }}</div>
                    <div class="text-xs text-gray-500">{{ $d }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
</x-layouts.public>

<x-layouts.public :title="'Booking — '.$salon->nama_salon">

<div class="max-w-4xl mx-auto px-6 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-6 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-[#4BA3CC]">Beranda</a><span>/</span>
        <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}" class="hover:text-[#4BA3CC]">{{ $salon->nama_salon }}</a><span>/</span>
        <span class="text-gray-600">Booking</span>
    </nav>

    <div class="flex gap-8">

        {{-- ── Left: Booking Steps ─────────────────────────────────────── --}}
        <div class="flex-1 min-w-0" x-data="bookingForm()">

            {{-- Step indicator --}}
            <div class="flex items-center gap-0 mb-8">
                @foreach(['Pilih Layanan','Pilih Waktu','Konfirmasi'] as $i => $label)
                    <div class="flex items-center {{ $i < 2 ? 'flex-1' : '' }}">
                        <div :class="{
                                'bg-[#1B2D6B] text-white': step >= {{ $i+1 }},
                                'bg-gray-100 text-gray-400': step < {{ $i+1 }}
                             }"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 transition-colors">
                            <span x-show="step > {{ $i+1 }}">✓</span>
                            <span x-show="step <= {{ $i+1 }}">{{ $i+1 }}</span>
                        </div>
                        <span :class="step >= {{ $i+1 }} ? 'text-[#1B2D6B] font-semibold' : 'text-gray-400'"
                              class="ml-2 text-sm transition-colors hidden sm:inline">{{ $label }}</span>
                        @if ($i < 2)
                            <div :class="step > {{ $i+1 }} ? 'bg-[#4BA3CC]' : 'bg-gray-200'"
                                 class="flex-1 h-0.5 mx-3 transition-colors"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- STEP 1: Pilih Layanan --}}
            <div x-show="step === 1" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Pilih Layanan</h2>
                <div class="space-y-3">
                    @foreach ($salon->services->where('status','active') as $svc)
                        <div @click="selectService({{ $svc->id_service }}, '{{ $svc->nama }}', {{ $svc->harga }}, {{ $svc->durasi }})"
                             :class="selectedServiceId === {{ $svc->id_service }}
                                     ? 'border-[#1B2D6B] bg-[#E8F4FB]'
                                     : 'border-gray-100 hover:border-[#4BA3CC] hover:bg-[#E8F4FB]/50'"
                             class="flex items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all">
                            <div>
                                <div class="font-medium text-gray-900">{{ $svc->nama }}</div>
                                @if ($svc->deskripsi)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($svc->deskripsi, 70) }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-1">⏱ {{ $svc->durasi }} menit</div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <div class="font-bold text-[#1B2D6B]">Rp {{ number_format($svc->harga, 0, ',', '.') }}</div>
                                <div x-show="selectedServiceId === {{ $svc->id_service }}"
                                     class="text-xs text-[#4BA3CC] font-semibold mt-1">✓ Dipilih</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- STEP 2: Pilih Tanggal & Waktu --}}
            <div x-show="step === 2" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Pilih Tanggal & Waktu</h2>

                {{-- Calendar --}}
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-[#E8F4FB] flex items-center justify-center transition-colors">‹</button>
                        <span class="font-semibold text-gray-800" x-text="monthLabel"></span>
                        <button @click="nextMonth()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-[#E8F4FB] flex items-center justify-center transition-colors">›</button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center mb-1">
                        @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $d)
                            <div class="text-xs font-bold text-gray-400 py-1">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center">
                        <template x-for="cell in calendarCells" :key="cell.key">
                            <div>
                                <button x-show="cell.day"
                                        @click="!cell.past && selectDate(cell.day)"
                                        :disabled="cell.past"
                                        :class="{
                                            'bg-[#1B2D6B] text-white': selectedDay === cell.day && !cell.past,
                                            'text-[#4BA3CC] font-bold': cell.today && selectedDay !== cell.day,
                                            'text-gray-300 cursor-not-allowed': cell.past,
                                            'hover:bg-[#E8F4FB] hover:text-[#1B2D6B]': !cell.past && selectedDay !== cell.day
                                        }"
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-sm mx-auto transition-all">
                                    <span x-text="cell.day"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Time slots --}}
                <div x-show="selectedDay">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Pilih Waktu</h3>
                    <div class="grid grid-cols-4 gap-2">
                        @php $times = ['09:00','09:30','10:00','10:30','11:00','11:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30']; @endphp
                        @foreach ($times as $time)
                            <button @click="selectTime('{{ $time }}')"
                                    :class="selectedTime === '{{ $time }}'
                                            ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]'
                                            : 'border-gray-200 text-gray-700 hover:border-[#4BA3CC]'"
                                    class="py-2.5 rounded-xl border text-sm font-medium transition-all">
                                {{ $time }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- STEP 3: Konfirmasi --}}
            <div x-show="step === 3" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Konfirmasi Booking</h2>
                <div class="bg-[#E8F4FB] rounded-2xl p-5 border border-[#C5E1F0] mb-6 space-y-3">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Salon</span><span class="font-medium">{{ $salon->nama_salon }}</span></div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Layanan</span>
                        <span class="font-medium" x-text="selectedServiceName"></span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Tanggal</span>
                        <span class="font-medium" x-text="selectedDay + ' ' + monthLabel"></span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Waktu</span>
                        <span class="font-medium" x-text="selectedTime"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-base border-t border-[#C5E1F0] pt-3">
                        <span>Total</span>
                        <span class="text-[#1B2D6B]" x-text="'Rp ' + selectedServicePrice.toLocaleString('id-ID')"></span>
                    </div>
                </div>

                <form action="{{ route('booking.store', $salon->slug ?? $salon->id_salon) }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_service" :value="selectedServiceId" />
                    <input type="hidden" name="tanggal" :value="bookingDate" />
                    <input type="hidden" name="waktu" :value="selectedTime" />

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                        <textarea name="catatan" rows="3"
                                  placeholder="Tambahkan catatan untuk salon…"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors resize-none"></textarea>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">Dengan menekan Konfirmasi, kamu setuju dengan <u>Syarat & Ketentuan</u> VIYGO. Pembayaran dilakukan di salon.</p>
                    <button type="submit"
                            class="w-full py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                        ✓ Konfirmasi Booking
                    </button>
                </form>
            </div>

            {{-- Navigation buttons --}}
            <div class="flex gap-3 mt-6">
                <button x-show="step > 1" @click="step--"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-full font-medium hover:border-[#1B2D6B] hover:text-[#1B2D6B] transition-colors">
                    ← Kembali
                </button>
                <button x-show="step < 3" @click="nextStep()"
                        :disabled="!canNext()"
                        :class="canNext() ? 'bg-[#1B2D6B] hover:bg-[#4BA3CC]' : 'bg-gray-200 cursor-not-allowed'"
                        class="flex-1 py-2.5 text-white font-semibold rounded-full transition-colors">
                    Lanjut →
                </button>
            </div>
        </div>

        {{-- ── Right: Summary ─────────────────────────────────────────── --}}
        <div class="hidden lg:block w-64 flex-shrink-0">
            <div class="sticky top-[160px] border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-xl flex-shrink-0">✂️</div>
                    <div>
                        <div class="font-semibold text-sm text-gray-900">{{ $salon->nama_salon }}</div>
                        <div class="text-xs text-gray-400">{{ $salon->kota?->nama }}</div>
                    </div>
                </div>
                <div class="text-sm space-y-2 text-gray-600">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#4BA3CC]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        {{ \Carbon\Carbon::parse($salon->opening_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($salon->closing_time)->format('H:i') }}
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 00.951-.69l1.07-3.292z"/></svg>
                        {{ number_format($salon->rating ?? 4.5, 1) }} ({{ $salon->total_review ?? 0 }} ulasan)
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function bookingForm() {
    const today = new Date();
    return {
        step: 1,
        selectedServiceId: null,
        selectedServiceName: '',
        selectedServicePrice: 0,
        selectedServiceDuration: 0,
        selectedDay: null,
        selectedTime: null,
        calMonth: today.getMonth(),
        calYear: today.getFullYear(),

        get monthLabel() {
            const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
            return months[this.calMonth] + ' ' + this.calYear;
        },

        get bookingDate() {
            if (!this.selectedDay) return '';
            return this.calYear + '-' + String(this.calMonth+1).padStart(2,'0') + '-' + String(this.selectedDay).padStart(2,'0');
        },

        get calendarCells() {
            const firstDay = new Date(this.calYear, this.calMonth, 1).getDay();
            const daysInMonth = new Date(this.calYear, this.calMonth+1, 0).getDate();
            const cells = [];
            for (let i = 0; i < firstDay; i++) cells.push({ key: 'e'+i, day: null });
            for (let d = 1; d <= daysInMonth; d++) {
                const date = new Date(this.calYear, this.calMonth, d);
                const todayMid = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                cells.push({ key: d, day: d, past: date < todayMid, today: date.toDateString() === today.toDateString() });
            }
            return cells;
        },

        selectService(id, name, price, dur) { this.selectedServiceId = id; this.selectedServiceName = name; this.selectedServicePrice = price; this.selectedServiceDuration = dur; },
        selectDate(day) { this.selectedDay = day; this.selectedTime = null; },
        selectTime(t) { this.selectedTime = t; },
        prevMonth() { if (this.calMonth === 0) { this.calMonth = 11; this.calYear--; } else this.calMonth--; },
        nextMonth() { if (this.calMonth === 11) { this.calMonth = 0; this.calYear++; } else this.calMonth++; },

        canNext() {
            if (this.step === 1) return !!this.selectedServiceId;
            if (this.step === 2) return !!this.selectedDay && !!this.selectedTime;
            return true;
        },
        nextStep() { if (this.canNext()) this.step++; },
    };
}
</script>
</x-layouts.public>

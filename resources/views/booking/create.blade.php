<x-layouts.public :title="'Booking — '.$salon->nama_salon">

<div class="max-w-4xl mx-auto px-6 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-gray-400 mb-6 flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:text-[#4BA3CC]">Home</a><span>/</span>
        <a href="{{ route('salon.show', $salon->slug ?? $salon->id_salon) }}" class="hover:text-[#4BA3CC]">{{ $salon->nama_salon }}</a><span>/</span>
        <span class="text-gray-600">Booking</span>
    </nav>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex gap-8">

        {{-- ── Left: Booking Steps ─────────────────────────────────────── --}}
        <div class="flex-1 min-w-0" x-data="bookingForm()" x-init="init()">

            {{-- Step indicator --}}
            <div class="flex items-center gap-0 mb-8">
                @foreach(['Pick a Service','Pick a Time','Confirm'] as $i => $label)
                    <div class="flex items-center {{ $i < 2 ? 'flex-1' : '' }}">
                        <div :class="{
                                'bg-[#1B2D6B] text-white': step >= {{ $i+1 }},
                                'bg-gray-100 text-gray-400': step < {{ $i+1 }}
                             }"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 transition-colors">
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

            {{-- STEP 1: Pick Services (multi-select) --}}
            <div x-show="step === 1" x-transition>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Pick Services</h2>
                    <span class="text-sm text-gray-500" x-show="selectedServiceIds.length > 0">
                        <span x-text="selectedServiceIds.length"></span> selected · Total
                        <span class="font-semibold text-[#1B2D6B]"
                              x-text="'£' + totalPrice.toLocaleString('en-GB', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                        · <span x-text="totalDuration"></span> min
                    </span>
                </div>
                <p class="text-xs text-gray-500 mb-3">You can choose more than one — services will be done back-to-back in one appointment.</p>
                <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                    @forelse ($salon->services->where('status','active') as $svc)
                        <div @click="toggleService({{ $svc->id_service }}, @js($svc->nama), {{ (float) $svc->harga }}, {{ (int) ($svc->durasi ?? 30) }})"
                             :class="isServiceSelected({{ $svc->id_service }})
                                     ? 'border-[#1B2D6B] bg-[#E8F4FB]'
                                     : 'border-gray-100 hover:border-[#4BA3CC] hover:bg-[#E8F4FB]/50'"
                             class="flex items-center justify-between p-4 rounded-xl border-2 cursor-pointer transition-all">
                            <div class="flex items-start gap-3">
                                {{-- Checkbox visual --}}
                                <div :class="isServiceSelected({{ $svc->id_service }})
                                              ? 'bg-[#1B2D6B] border-[#1B2D6B]'
                                              : 'border-gray-300'"
                                     class="w-5 h-5 rounded border-2 flex items-center justify-center shrink-0 mt-0.5 transition-colors">
                                    <svg x-show="isServiceSelected({{ $svc->id_service }})" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ $svc->nama }}</div>
                                    @if ($svc->deskripsi)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($svc->deskripsi, 70) }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-1">⏱ {{ $svc->durasi }} min</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-4">
                                <div class="font-bold text-[#1B2D6B]">£{{ number_format($svc->harga, 2, '.', ',') }}</div>
                                <div x-show="isServiceSelected({{ $svc->id_service }})"
                                     class="text-xs text-[#4BA3CC] font-semibold mt-1">✓ Selected</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No services available for this salon.</p>
                    @endforelse
                </div>
            </div>

            {{-- STEP 2: Pick a Time --}}
            <div x-show="step === 2" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Pick Date &amp; Time</h2>

                {{-- Staff picker --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Staff</label>
                    <select x-model.number="selectedStaffId" @change="loadSlots()"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors">
                        <option value="0">Any staff (we'll pick the first available)</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id_staff }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-[#E8F4FB] flex items-center justify-center transition-colors">‹</button>
                        <span class="font-semibold text-gray-800" x-text="monthLabel"></span>
                        <button @click="nextMonth()" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-[#E8F4FB] flex items-center justify-center transition-colors">›</button>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center mb-1">
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
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
                                            'bg-[#1B2D6B] text-white': isSelectedCell(cell.day) && !cell.past,
                                            'text-[#4BA3CC] font-bold': cell.today && !isSelectedCell(cell.day),
                                            'text-gray-300 cursor-not-allowed': cell.past,
                                            'hover:bg-[#E8F4FB] hover:text-[#1B2D6B]': !cell.past && !isSelectedCell(cell.day)
                                        }"
                                        class="w-9 h-9 rounded-full flex items-center justify-center text-sm mx-auto transition-all">
                                    <span x-text="cell.day"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div x-show="selectedDay">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Pick a Time</h3>

                    <div x-show="loadingSlots" class="text-sm text-gray-400 py-4">
                        Checking availability…
                    </div>

                    <div x-show="!loadingSlots && slots.length === 0" class="text-sm text-gray-400 py-4">
                        No slots available for this date. Try another day or a different staff member.
                    </div>

                    <div x-show="!loadingSlots && slots.length > 0" class="grid grid-cols-4 gap-2">
                        <template x-for="slot in slots" :key="slot.time">
                            <button @click="selectTime(slot.time, slot.staff)"
                                    :class="selectedTime === slot.time
                                            ? 'bg-[#1B2D6B] text-white border-[#1B2D6B]'
                                            : 'border-gray-200 text-gray-700 hover:border-[#4BA3CC]'"
                                    class="py-2.5 rounded-xl border text-sm font-medium transition-all"
                                    x-text="slot.time">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Confirm --}}
            <div x-show="step === 3" x-transition>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Confirm Your Booking</h2>
                <div class="bg-[#E8F4FB] rounded-2xl p-5 border border-[#C5E1F0] mb-6 space-y-3">
                    <div class="flex justify-between text-sm"><span class="text-gray-500">Salon</span><span class="font-medium">{{ $salon->nama_salon }}</span></div>
                    <div class="border-t border-[#C5E1F0] pt-3">
                        <div class="text-sm text-gray-500 mb-2">Services (<span x-text="selectedServices.length"></span>)</div>
                        <ul class="space-y-1.5">
                            <template x-for="svc in selectedServices" :key="svc.id">
                                <li class="flex justify-between text-sm">
                                    <span class="text-gray-700">
                                        <span x-text="svc.name"></span>
                                        <span class="text-xs text-gray-400" x-text="'(' + svc.duration + ' min)'"></span>
                                    </span>
                                    <span class="font-medium" x-text="'£' + svc.price.toLocaleString('en-GB', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Date</span>
                        <span class="font-medium" x-text="selectedDay + ' ' + monthLabel"></span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Time</span>
                        <span class="font-medium" x-text="selectedTime + ' (' + totalDuration + ' min total)'"></span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-[#C5E1F0] pt-3">
                        <span class="text-gray-500">Staff</span>
                        <span class="font-medium" x-text="selectedStaffName"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-base border-t border-[#C5E1F0] pt-3">
                        <span>Total</span>
                        <span class="text-[#1B2D6B]" x-text="'£' + totalPrice.toLocaleString('en-GB', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>

                <form action="{{ route('booking.store', $salon->slug ?? $salon->id_salon) }}" method="POST">
                    @csrf
                    <template x-for="sid in selectedServiceIds" :key="sid">
                        <input type="hidden" name="id_service[]" :value="sid" />
                    </template>
                    <input type="hidden" name="tanggal"  :value="bookingDate" />
                    <input type="hidden" name="waktu"    :value="selectedTime" />
                    <input type="hidden" name="id_staff" :value="selectedStaffId" />

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                        <textarea name="catatan" rows="3"
                                  placeholder="Add a note for the salon…"
                                  class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors resize-none"></textarea>
                    </div>
                    <p class="text-xs text-gray-400 mb-4">
                        By confirming, you agree to VIYGO's
                        <a href="{{ route('static.terms') }}" class="underline">Terms &amp; Conditions</a>.
                        You'll be redirected to a secure payment screen next.
                    </p>
                    <button type="submit"
                            class="w-full py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
                        Continue to Payment →
                    </button>
                </form>
            </div>

            {{-- Navigation --}}
            <div class="flex gap-3 mt-6">
                <button x-show="step > 1" @click="step--"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-full font-medium hover:border-[#1B2D6B] hover:text-[#1B2D6B] transition-colors">
                    ← Back
                </button>
                <button x-show="step < 3" @click="nextStep()"
                        :disabled="!canNext()"
                        :class="canNext() ? 'bg-[#1B2D6B] hover:bg-[#4BA3CC]' : 'bg-gray-200 cursor-not-allowed'"
                        class="flex-1 py-2.5 text-white font-semibold rounded-full transition-colors">
                    Next →
                </button>
            </div>
        </div>

        {{-- ── Right: Summary ─────────────────────────────────────────── --}}
        <div class="hidden lg:block w-64 shrink-0">
            <div class="sticky top-[160px] border border-gray-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-xl shrink-0">✂️</div>
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
                        <span class="text-amber-400">★</span>
                        {{ number_format($salon->rating ?? 4.5, 1) }} ({{ number_format($salon->total_review ?? 0) }} reviews)
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function bookingForm() {
    const today = new Date();
    const slotsUrl = @json(route('booking.slots', $salon->slug ?? $salon->id_salon));

    return {
        step: 1,
        // Multi-service: list of {id, name, price, duration}
        selectedServices: [],
        selectedDay: null,
        // Year/month the user picked the day in. Without this, day-15
        // stays highlighted when you scroll past it to a new month.
        selectedYear: null,
        selectedMonth: null,
        selectedTime: null,
        selectedStaffId: 0,
        selectedStaffName: 'Any staff',
        slots: [],
        loadingSlots: false,
        calMonth: today.getMonth(),
        calYear: today.getFullYear(),

        init() {},

        // True only when the cell's day is the one the user actually chose,
        // AND the calendar is still showing the month they chose it in.
        isSelectedCell(day) {
            return this.selectedDay === day
                && this.selectedYear === this.calYear
                && this.selectedMonth === this.calMonth;
        },

        get monthLabel() {
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            return months[this.calMonth] + ' ' + this.calYear;
        },

        get bookingDate() {
            if (!this.selectedDay || this.selectedYear === null) return '';
            return this.selectedYear
                + '-' + String(this.selectedMonth + 1).padStart(2,'0')
                + '-' + String(this.selectedDay).padStart(2,'0');
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

        // ─── Multi-service helpers ───────────────────────────
        get selectedServiceIds() {
            return this.selectedServices.map(s => s.id);
        },
        get totalPrice() {
            return this.selectedServices.reduce((sum, s) => sum + Number(s.price), 0);
        },
        get totalDuration() {
            return this.selectedServices.reduce((sum, s) => sum + Number(s.duration), 0);
        },
        isServiceSelected(id) {
            return this.selectedServices.some(s => s.id === id);
        },
        toggleService(id, name, price, dur) {
            const idx = this.selectedServices.findIndex(s => s.id === id);
            if (idx >= 0) {
                this.selectedServices.splice(idx, 1);
            } else {
                this.selectedServices.push({ id, name, price: Number(price), duration: Number(dur) });
            }
            // Slot/time invalidate karena total duration berubah.
            this.slots = [];
            this.selectedTime = null;
        },

        selectDate(day) {
            this.selectedDay = day;
            this.selectedYear = this.calYear;
            this.selectedMonth = this.calMonth;
            this.selectedTime = null;
            this.loadSlots();
        },

        selectTime(time, staffOptions) {
            this.selectedTime = time;
            // If user picked "Any staff", show the first staff returned for the slot.
            if (this.selectedStaffId === 0 && staffOptions && staffOptions.length) {
                this.selectedStaffName = staffOptions[0].id === 0 ? 'Any staff' : staffOptions[0].name;
            } else if (this.selectedStaffId !== 0) {
                const match = staffOptions?.find(s => s.id === this.selectedStaffId);
                this.selectedStaffName = match ? match.name : 'Selected staff';
            } else {
                this.selectedStaffName = 'Any staff';
            }
        },

        prevMonth() { if (this.calMonth === 0) { this.calMonth = 11; this.calYear--; } else this.calMonth--; },
        nextMonth() { if (this.calMonth === 11) { this.calMonth = 0; this.calYear++; } else this.calMonth++; },

        async loadSlots() {
            if (this.selectedServiceIds.length === 0 || !this.selectedDay) {
                this.slots = [];
                return;
            }
            this.loadingSlots = true;
            this.slots = [];
            this.selectedTime = null;

            try {
                const url = new URL(slotsUrl, window.location.origin);
                this.selectedServiceIds.forEach(id => url.searchParams.append('service_ids[]', id));
                url.searchParams.set('date', this.bookingDate);
                url.searchParams.set('staff_id', this.selectedStaffId || 0);

                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!res.ok) {
                    this.slots = [];
                    return;
                }

                const body = await res.json();
                this.slots = body.slots || [];
            } catch (e) {
                console.error('slot fetch failed', e);
                this.slots = [];
            } finally {
                this.loadingSlots = false;
            }
        },

        canNext() {
            if (this.step === 1) return this.selectedServiceIds.length > 0;
            if (this.step === 2) return !!this.selectedDay && !!this.selectedTime;
            return true;
        },

        nextStep() { if (this.canNext()) this.step++; },
    };
}
</script>
</x-layouts.public>

<x-layouts.public title="Leave a review">
<div class="max-w-2xl mx-auto px-6 py-10">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.bookings', ['tab' => 'selesai']) }}"
           class="text-[#4BA3CC] hover:underline text-sm">← My Bookings</a>
        <h1 class="text-2xl text-[#1B2D6B] font-serif">How was your visit?</h1>
    </div>

    <div class="border border-gray-100 rounded-2xl overflow-hidden mb-6 bg-gray-50">
        <div class="p-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-[#E8F4FB] flex items-center justify-center text-2xl">✂️</div>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900">{{ $order->salon->nama_salon }}</div>
                <div class="text-sm text-gray-500">
                    {{ $order->details->pluck('service.nama')->implode(', ') }}
                </div>
                <div class="text-xs text-gray-400 mt-0.5">
                    {{ \Carbon\Carbon::parse($order->date_order)->isoFormat('D MMM Y') }}
                    · <span class="font-mono">{{ $order->kode_order }}</span>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('akun.review.store', $order->kode_order) }}"
          x-data="{ rating: {{ old('rating', 0) }} }"
          class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Your rating</label>
            <div class="flex gap-2 items-center">
                <template x-for="star in 5" :key="star">
                    <button type="button"
                            @click="rating = star"
                            class="text-4xl leading-none transition-transform hover:scale-110 focus:outline-none"
                            :class="star <= rating ? 'text-amber-400' : 'text-gray-300'"
                            :aria-label="`Rate ${star} stars`">
                        ★
                    </button>
                </template>
                <span class="ml-3 text-sm text-gray-500" x-text="rating ? rating + ' / 5' : 'Tap a star'"></span>
            </div>
            <input type="hidden" name="rating" :value="rating" />
        </div>

        <div>
            <label for="komentar" class="block text-sm font-semibold text-gray-700 mb-2">
                Tell us about your visit
            </label>
            <textarea name="komentar"
                      id="komentar"
                      rows="6"
                      required
                      maxlength="2000"
                      placeholder="What did you love? Anything to improve? Helpful, polite reviews help other customers."
                      class="w-full rounded-xl border-gray-200 focus:border-[#4BA3CC] focus:ring focus:ring-[#4BA3CC]/20 text-sm"
                      >{{ old('komentar') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Up to 2,000 characters.</p>
        </div>

        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('akun.bookings', ['tab' => 'selesai']) }}"
               class="text-sm text-gray-500 hover:text-gray-800">Not now</a>
            <button type="submit"
                    class="px-6 py-3 bg-[#1B2D6B] text-white rounded-full font-semibold hover:bg-[#4BA3CC] transition-colors">
                Submit review
            </button>
        </div>
    </form>

    <p class="text-xs text-gray-400 mt-6">
        Reviews are visible by default. VIYGO admins may hide reviews that break the
        <a href="{{ route('static.terms') }}" class="underline">community guidelines</a>.
    </p>

</div>
</x-layouts.public>

<x-layouts.public title="Payment">

@php
    $snapJsUrl = $production
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
@endphp

<div class="max-w-2xl mx-auto px-6 py-12">

    <div class="text-center mb-8">
        <div class="inline-flex w-16 h-16 rounded-full bg-[#E8F4FB] items-center justify-center text-3xl mb-4">
            🔒
        </div>
        <h1 class="text-2xl font-serif text-[#1B2D6B]">Complete your payment</h1>
        <p class="text-sm text-gray-500 mt-2">
            We use Midtrans Snap — a secure pop-up that supports cards,
            virtual accounts, e-wallets, and more.
        </p>
    </div>

    <div class="border border-gray-100 rounded-2xl p-6 bg-gray-50 mb-6 space-y-3 text-sm">
        <div class="flex justify-between">
            <span class="text-gray-500">Order code</span>
            <span class="font-mono font-semibold">{{ $order->kode_order }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-3">
            <span class="text-gray-500">Salon</span>
            <span class="font-medium">{{ $order->salon->nama_salon }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-3">
            <span class="text-gray-500">Date</span>
            <span class="font-medium">
                {{ \Carbon\Carbon::parse($order->date_order)->isoFormat('D MMM Y') }}
            </span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-3">
            <span class="text-gray-500">Services</span>
            <span class="font-medium text-right">
                {{ $order->details->pluck('service.nama')->implode(', ') }}
            </span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-semibold">
            <span>Total</span>
            <span class="text-[#1B2D6B]">
                £{{ number_format($order->total_pembayaran, 2) }}
            </span>
        </div>
    </div>

    @if (! $clientKey)
        <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-800 p-4 text-sm mb-6">
            <p class="font-semibold mb-1">Payment gateway not configured</p>
            <p class="text-xs">
                Set <code>MIDTRANS_SERVER_KEY</code> and <code>MIDTRANS_CLIENT_KEY</code>
                in your <code>.env</code> (sandbox keys are fine for development),
                then reload this page.
            </p>
        </div>
    @endif

    <div id="pay-status" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

    <button id="pay-button"
            type="button"
            @if (! $clientKey) disabled @endif
            class="w-full py-3.5 bg-[#1B2D6B] text-white font-semibold rounded-full
                   hover:bg-[#4BA3CC] transition-colors disabled:bg-gray-300
                   disabled:cursor-not-allowed">
        Pay £{{ number_format($order->total_pembayaran, 2) }}
    </button>

    <p class="text-xs text-gray-400 text-center mt-4">
        After successful payment, your booking moves from “pending” to “confirmed”
        and the salon gets a notification.
        Need help? <a href="{{ route('static.help') }}" class="underline">Contact support</a>.
    </p>

</div>

@if ($clientKey)
<script src="{{ $snapJsUrl }}"
        data-client-key="{{ $clientKey }}"></script>
<script>
(function () {
    const btn      = document.getElementById('pay-button');
    const statusEl = document.getElementById('pay-status');

    function showStatus(msg, kind = 'info') {
        statusEl.textContent = msg;
        statusEl.className = 'mb-4 p-3 rounded-lg text-sm '
            + (kind === 'error' ? 'bg-red-50 border border-red-100 text-red-700'
            :  kind === 'success' ? 'bg-green-50 border border-green-100 text-green-700'
            :  'bg-blue-50 border border-blue-100 text-blue-700');
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.addEventListener('click', async function () {
        btn.disabled = true;
        btn.textContent = 'Preparing payment…';

        try {
            const res = await fetch(@json(route('booking.payment.token', $order->kode_order)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (! res.ok) {
                const body = await res.json().catch(() => ({}));
                throw new Error(body.error || ('HTTP ' + res.status));
            }

            const { snap_token } = await res.json();

            window.snap.pay(snap_token, {
                onSuccess: async function () {
                    showStatus('Payment successful! Verifying…', 'success');
                    btn.disabled = true;
                    btn.textContent = 'Verifying payment…';

                    // Server-side verify the transaction via Midtrans API
                    // so the order status updates even if webhook never arrives.
                    try {
                        await fetch(@json(route('booking.payment.finish', $order->kode_order)), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (e) {
                        console.warn('Finish endpoint error (webhook will handle):', e);
                    }

                    showStatus('Payment confirmed! Redirecting…', 'success');
                    window.location.href = @json(route('booking.konfirmasi', $order->kode_order));
                },
                onPending: function () {
                    showStatus('Payment is pending. We will confirm once the bank settles.', 'info');
                    btn.disabled = false;
                    btn.textContent = 'Try again';
                },
                onError: function (err) {
                    console.error(err);
                    showStatus('Payment failed. Please try another method.', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Try again';
                },
                onClose: function () {
                    showStatus('Pop-up closed before payment finished.', 'info');
                    btn.disabled = false;
                    btn.textContent = 'Pay £{{ number_format($order->total_pembayaran, 2) }}';
                },
            });
        } catch (err) {
            console.error(err);
            showStatus(err.message || 'Could not start payment.', 'error');
            btn.disabled = false;
            btn.textContent = 'Try again';
        }
    });
})();
</script>
@endif

</x-layouts.public>

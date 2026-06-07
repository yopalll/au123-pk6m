<x-layouts.public title="Pembayaran">
<div class="max-w-md mx-auto px-4 sm:px-6 py-10 text-center">
    <p class="text-5xl mb-4">💳</p>
    <h1 class="text-2xl font-semibold mb-1" style="font-family:'DM Serif Display',serif">Pembayaran</h1>
    <p class="text-sm text-gray-500 mb-6">Pesanan <span class="font-mono font-semibold">{{ $order->kode_order }}</span></p>

    <div class="bg-white border border-gray-100 rounded-2xl p-6 mb-6 text-left">
        <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Ongkir</span><span>{{ $order->biaya_kirim > 0 ? 'Rp '.number_format($order->biaya_kirim,0,',','.') : 'GRATIS' }}</span></div>
            @if ($order->total_diskon > 0)<div class="flex justify-between text-emerald-600"><span>Diskon</span><span>- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</span></div>@endif
            @if ($order->potongan_poin > 0)<div class="flex justify-between text-emerald-600"><span>Poin</span><span>- Rp {{ number_format($order->potongan_poin, 0, ',', '.') }}</span></div>@endif
            <div class="flex justify-between font-bold text-lg border-t border-gray-100 pt-2 mt-2"><span>Total</span><span class="text-[#1B2D6B]">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span></div>
        </div>
    </div>

    <button id="pay-btn" class="w-full py-3 bg-[#1B2D6B] text-white text-sm font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
        Bayar Sekarang
    </button>
    <a href="{{ route('shop.order.show', $order->kode_order) }}" class="block mt-3 text-sm text-gray-400 hover:underline">Lihat detail pesanan</a>
    <p id="pay-msg" class="text-sm text-red-500 mt-3"></p>
</div>

<x-slot:scripts>
@if ($clientKey)
    <script src="https://app.{{ config('services.midtrans.is_production') ? '' : 'sandbox.' }}midtrans.com/snap/snap.js"
            data-client-key="{{ $clientKey }}"></script>
@endif
<script>
document.getElementById('pay-btn').addEventListener('click', async function () {
    const msg = document.getElementById('pay-msg'); msg.textContent = '';
    this.disabled = true; this.textContent = 'Memproses…';
    try {
        const res = await fetch('{{ route('shop.order.payment.token', $order->kode_order) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        if (!window.snap) throw new Error('Midtrans Snap belum termuat (cek MIDTRANS_CLIENT_KEY).');

        window.snap.pay(data.snap_token, {
            onSuccess: (r) => submitFinish(r),
            onPending: (r) => submitFinish(r),
            onError:   ()  => { msg.textContent = 'Pembayaran gagal.'; resetBtn(); },
            onClose:   ()  => resetBtn(),
        });
    } catch (e) {
        msg.textContent = e.message || 'Terjadi kesalahan.';
        resetBtn();
    }
});
function resetBtn() { const b = document.getElementById('pay-btn'); b.disabled = false; b.textContent = 'Bayar Sekarang'; }
function submitFinish(r) {
    const f = document.createElement('form');
    f.method = 'POST'; f.action = '{{ route('shop.order.payment.finish', $order->kode_order) }}';
    const fields = { _token: '{{ csrf_token() }}', transaction_status: r.transaction_status || 'pending', transaction_id: r.transaction_id || '', payment_type: r.payment_type || '' };
    for (const k in fields) { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = fields[k]; f.appendChild(i); }
    document.body.appendChild(f); f.submit();
}
</script>
</x-slot:scripts>
</x-layouts.public>

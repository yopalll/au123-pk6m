<?php

namespace App\Http\Controllers;

use App\Constants\OrderDetailStatus;
use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Promo;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Staff;
use App\Services\BookingSlotService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Transaction;

class BookingController extends Controller
{
    public function __construct(
        protected BookingSlotService $slots,
    ) {}

    public function create(string $slug)
    {
        $salon = $this->loadSalon($slug);

        $staff = Staff::where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id_staff', 'name']);

        return view('booking.create', compact('salon', 'staff'));
    }

    /**
     * JSON endpoint used by the wizard to fetch dynamic time slots.
     * Menerima multi-service: hitung slot berdasarkan TOTAL durasi semua service.
     */
    public function getSlots(Request $request, string $slug)
    {
        $data = $request->validate([
            'service_ids'   => 'required|array|min:1|max:20',  // SEC-06: cap at 20 services
            'service_ids.*' => 'integer|exists:service,id_service',
            'date'          => 'required|date|after_or_equal:today',
            'staff_id'      => 'nullable|integer',
        ]);

        $salon    = $this->loadSalon($slug);
        $services = Service::where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->whereIn('id_service', $data['service_ids'])
            ->get();

        if ($services->isEmpty()) {
            return response()->json(['date' => $data['date'], 'slots' => []]);
        }

        $totalDuration = (int) $services->sum(fn ($s) => max(15, (int) ($s->durasi ?? 30)));

        $date = CarbonImmutable::parse($data['date']);
        $staffId = $data['staff_id'] ?? null;
        if ($staffId === 0) {
            $staffId = null;
        }

        $slots = $this->slots->availableSlotsForDuration(
            $salon,
            $totalDuration,
            $date,
            $staffId,
            $services->pluck('id_service')->all(),
        );

        return response()->json([
            'date'  => $date->toDateString(),
            'slots' => $slots->values(),
        ]);
    }

    public function validatePromo(Request $request)
    {
        $data = $request->validate([
            'kode_promo' => 'required|string|max:50',
            'total'      => 'required|numeric|min:0',
        ]);

        $promo = Promo::byCode($data['kode_promo'])->first();

        if (! $promo) {
            return response()->json(['valid' => false, 'message' => 'Promo code not found or has expired.']);
        }
        if (! $promo->meetsMinimum((float) $data['total'])) {
            return response()->json([
                'valid'   => false,
                'message' => 'Minimum transaction of £' . number_format($promo->min_transaksi, 2) . ' required for this promo.',
            ]);
        }
        if ($promo->isSoldOut()) {
            return response()->json(['valid' => false, 'message' => 'This promo code has run out.']);
        }

        $discount = $promo->calculateDiscount((float) $data['total']);

        return response()->json([
            'valid'      => true,
            'id_promo'   => $promo->id_promo,
            'nama_promo' => $promo->nama_promo,
            'diskon'     => $discount,
            'tipe'       => $promo->tipe_promo === 'percentage'
                ? number_format($promo->diskon, 0) . '% off'
                : '£' . number_format($promo->diskon, 2) . ' off',
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $data = $request->validate([
            'id_service'   => 'required|array|min:1|max:20',  // SEC-06: cap at 20 services
            'id_service.*' => 'integer|exists:service,id_service',
            'tanggal'      => 'required|date|after_or_equal:today',
            'waktu'        => 'required|string',
            'id_staff'     => 'nullable|integer',
            'catatan'      => 'nullable|string|max:1000',
            'kode_promo'   => 'nullable|string|max:50',
        ]);

        $salon = $this->loadSalon($slug);

        $services = Service::where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->whereIn('id_service', $data['id_service'])
            ->get();

        if ($services->count() !== count($data['id_service'])) {
            return back()->withInput()->withErrors([
                'id_service' => 'One or more selected services are no longer available.',
            ]);
        }

        $totalDuration = (int) $services->sum(fn ($s) => max(15, (int) ($s->durasi ?? 30)));
        $totalPrice    = (float) $services->sum('harga');

        // Validate and apply promo code if provided.
        $promo        = null;
        $totalDiskon  = 0.0;
        $finalPrice   = $totalPrice;

        if (! empty($data['kode_promo'])) {
            $promo = Promo::byCode($data['kode_promo'])->first();

            if (! $promo) {
                return back()->withInput()->withErrors(['kode_promo' => 'Promo code not found or has expired.']);
            }
            if (! $promo->meetsMinimum($totalPrice)) {
                return back()->withInput()->withErrors([
                    'kode_promo' => 'Minimum transaction of £' . number_format($promo->min_transaksi, 2) . ' required.',
                ]);
            }
            if ($promo->isSoldOut()) {
                return back()->withInput()->withErrors(['kode_promo' => 'This promo code has run out.']);
            }

            $totalDiskon = $promo->calculateDiscount($totalPrice);
            $finalPrice  = round($totalPrice - $totalDiskon, 2);
        }

        $date = CarbonImmutable::parse($data['tanggal']);
        $staffId = isset($data['id_staff']) && (int) $data['id_staff'] !== 0
            ? (int) $data['id_staff']
            : null;

        if ($staffId) {
            Staff::where('id_staff', $staffId)
                ->where('id_salon', $salon->id_salon)
                ->where('status', 'active')
                ->firstOrFail();
        }

        // Re-verify slot dengan total durasi gabungan.
        if (! $this->slots->isSlotAvailableForDuration(
            $salon, $totalDuration, $date, $data['waktu'], $staffId,
            $services->pluck('id_service')->all()
        )) {
            return back()
                ->withInput()
                ->withErrors([
                    'waktu' => 'Sorry, that slot was just taken. Please pick another.',
                ]);
        }

        $resolvedStaffId = $staffId ?? $this->slots->pickStaffForSlotForDuration(
            $salon, $totalDuration, $date, $data['waktu'],
            $services->pluck('id_service')->all(),
        );

        // P0-4 / BUG-A01: Wrap check+insert in transaction with lockForUpdate
        // to prevent double-booking race condition.
        try {
            $order = DB::transaction(function () use ($data, $salon, $services, $resolvedStaffId, $totalDuration, $date, $staffId, $promo, $totalDiskon, $finalPrice) {
                // Pessimistic lock on conflicting order_detail rows
                // to serialize concurrent booking attempts for the same slot.
                OrderDetail::whereIn('id_staff', array_filter([$resolvedStaffId]))
                    ->whereHas('order', fn ($q) => $q->whereDate('date_order', $data['tanggal']))
                    ->lockForUpdate()
                    ->get();

                // Re-verify slot inside the locked transaction.
                if (! $this->slots->isSlotAvailableForDuration(
                    $salon, $totalDuration, $date, $data['waktu'], $resolvedStaffId,
                    $services->pluck('id_service')->all()
                )) {
                    throw new \RuntimeException('SLOT_TAKEN');
                }

                if ($promo) {
                    // Re-check stock inside the transaction to prevent race conditions.
                    if ($promo->stock > 0 && $promo->used_counter >= $promo->stock) {
                        throw new \RuntimeException('PROMO_EXHAUSTED');
                    }
                    $promo->increment('used_counter');
                }

            $order = Order::create([
                'id_user'          => auth()->id(),
                'id_salon'         => $salon->id_salon,
                'id_promo'         => $promo?->id_promo,
                'kode_order'       => 'VYG-' . strtoupper(Str::random(8)),
                'date_order'       => $data['tanggal'],
                'total_pembayaran' => $finalPrice,
                'total_diskon'     => $totalDiskon,
                'status'           => OrderStatus::PENDING,
            ]);

            // Susun order_detail berurutan: service-2 mulai setelah service-1 selesai, dst.
            $cursor = Carbon::createFromFormat('H:i', $data['waktu']);
            foreach ($services as $idx => $service) {
                $start = $cursor->format('H:i');
                $cursor->addMinutes(max(15, (int) ($service->durasi ?? 30)));
                $end = $cursor->format('H:i');

                OrderDetail::create([
                    'id_order'       => $order->id_order,
                    'id_service'     => $service->id_service,
                    'id_staff'       => $resolvedStaffId,
                    'start_time'     => $start,
                    'end_time'       => $end,
                    'harga_at_order' => $service->harga,
                    'subtotal'       => $service->harga,
                    'catatan'        => $idx === 0 ? ($data['catatan'] ?? null) : null,
                    'status'         => OrderDetailStatus::PENDING,  // BUG-A18
                ]);
            }

                return $order;
            });
        } catch (QueryException $e) {
            // SQLSTATE 23000 = duplicate / constraint violation (BUG-A01).
            if ($e->getCode() === '23000') {
                return back()->withInput()->withErrors([
                    'waktu' => 'That slot was just taken by another booking. Please pick another time.',
                ]);
            }
            throw $e;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'SLOT_TAKEN') {
                return back()->withInput()->withErrors([
                    'waktu' => 'Sorry, that slot was just taken. Please pick another.',
                ]);
            }
            if ($e->getMessage() === 'PROMO_EXHAUSTED') {
                return back()->withInput()->withErrors([
                    'kode_promo' => 'This promo code just ran out. Please try another.',
                ]);
            }
            throw $e;
        }

        return redirect()
            ->route('booking.payment', $order->kode_order)
            ->with('success', 'Booking created. Complete payment to confirm.');
    }

    public function konfirmasi(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['salon.kota', 'details.service', 'details.staff'])
            ->firstOrFail();

        return view('booking.konfirmasi', compact('order'));
    }

    public function batal(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
            ->firstOrFail();

        // BUG-A07: Use actual appointment start_time, not midnight of date_order.
        // date_order is cast to 'date' (Carbon at 00:00), so isPast() would be
        // true the moment the booking day begins — blocking same-day cancellation.
        $firstDetail = $order->details()->orderBy('start_time')->first();
        $startAt = $firstDetail && $firstDetail->start_time
            ? Carbon::parse($order->date_order->toDateString() . ' ' . $firstDetail->start_time)
            : $order->date_order->copy()->endOfDay();

        if ($startAt->isPast()) {
            return back()->withErrors([
                'cancel' => 'This appointment has already started and cannot be cancelled.',
            ]);
        }

        // Trigger Midtrans refund if the order was already paid.
        if ($order->status === OrderStatus::CONFIRMED) {
            $payment = $order->pembayaran()->first();
            if ($payment && $payment->id_transaksi) {
                try {
                    Transaction::refund($payment->id_transaksi, [
                        // BUG-A08: Append timestamp so each attempt generates a unique refund_key.
                        // Midtrans rejects duplicate refund_key with HTTP 412.
                        'refund_key' => 'refund_' . $order->kode_order . '_' . now()->format('YmdHis'),
                        'amount'     => (int) round((float) $order->total_pembayaran),
                        'reason'     => 'Customer requested cancellation',
                    ]);
                    $payment->update(['status_pembayaran' => 'refunded']);
                } catch (\Throwable $e) {
                    \Log::error('Midtrans refund failed', [
                        'order' => $order->kode_order,
                        'error' => $e->getMessage(),
                    ]);

                    return back()->withErrors([
                        'cancel' => 'Refund failed. Please contact support to cancel this paid booking.',
                    ]);
                }
            }
        }

        $order->update(['status' => OrderStatus::CANCELED]);

        return back()->with('success', 'Booking cancelled successfully.');
    }

    protected function loadSalon(string $slug): Salon
    {
        return Salon::active()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id_salon', $slug))
            ->with([
                'kota',
                'services' => fn ($q) => $q->where('status', 'active')->with('kategori'),
                'primaryImage',
            ])
            ->firstOrFail();
    }
}

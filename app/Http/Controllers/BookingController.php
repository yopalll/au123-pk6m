<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Staff;
use App\Services\BookingSlotService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'service_ids'   => 'required|array|min:1',
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

    public function store(Request $request, string $slug)
    {
        $data = $request->validate([
            'id_service'   => 'required|array|min:1',
            'id_service.*' => 'integer|exists:service,id_service',
            'tanggal'      => 'required|date|after_or_equal:today',
            'waktu'        => 'required|string',
            'id_staff'     => 'nullable|integer',
            'catatan'      => 'nullable|string|max:1000',
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

        $order = DB::transaction(function () use ($data, $salon, $services, $resolvedStaffId, $totalPrice) {
            $order = Order::create([
                'id_user'          => auth()->id(),
                'id_salon'         => $salon->id_salon,
                'id_promo'         => null,
                'kode_order'       => 'VYG-' . strtoupper(Str::random(8)),
                'date_order'       => $data['tanggal'],
                'total_pembayaran' => $totalPrice,
                'total_diskon'     => 0,
                'status'           => 'pending',
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
                    'status'         => 'pending',
                ]);
            }

            return $order;
        });

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
            ->where('status', 'pending')
            ->firstOrFail();

        // Enforce that the appointment hasn't started yet.
        if ($order->date_order && $order->date_order->isPast()) {
            return back()->withErrors([
                'cancel' => 'This appointment has already passed and cannot be cancelled.',
            ]);
        }

        $order->update(['status' => 'canceled']);

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

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
     */
    public function getSlots(Request $request, string $slug)
    {
        $data = $request->validate([
            'service_id' => 'required|integer|exists:service,id_service',
            'date'       => 'required|date|after_or_equal:today',
            'staff_id'   => 'nullable|integer',
        ]);

        $salon   = $this->loadSalon($slug);
        $service = Service::where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->findOrFail($data['service_id']);

        $date = CarbonImmutable::parse($data['date']);
        $staffId = $data['staff_id'] ?? null;
        if ($staffId === 0) {
            $staffId = null;
        }

        $slots = $this->slots->availableSlots($salon, $service, $date, $staffId);

        return response()->json([
            'date'  => $date->toDateString(),
            'slots' => $slots->values(),
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $data = $request->validate([
            'id_service' => 'required|exists:service,id_service',
            'tanggal'    => 'required|date|after_or_equal:today',
            'waktu'      => 'required|string',
            'id_staff'   => 'nullable|integer',
            'catatan'    => 'nullable|string|max:1000',
        ]);

        $salon = $this->loadSalon($slug);

        $service = Service::where('id_salon', $salon->id_salon)
            ->where('status', 'active')
            ->findOrFail($data['id_service']);

        $date = CarbonImmutable::parse($data['tanggal']);
        $staffId = $data['id_staff'] ?? null;
        if ($staffId === 0) {
            $staffId = null;
        }

        // Re-verify the slot is still bookable. Two customers racing for the
        // same slot only one wins — the loser sees a redirect-back with an error
        // instead of a successful double-booking.
        if (! $this->slots->isSlotAvailable($salon, $service, $date, $data['waktu'], $staffId)) {
            return back()
                ->withInput()
                ->withErrors([
                    'waktu' => 'Sorry, that slot was just taken. Please pick another.',
                ]);
        }

        // If user said "Any staff", pick a concrete one now.
        $resolvedStaffId = $staffId
            ?? $this->slots->pickStaffForSlot($salon, $service, $date, $data['waktu']);

        $order = DB::transaction(function () use ($data, $salon, $service, $resolvedStaffId) {
            $order = Order::create([
                'id_user'          => auth()->id(),
                'id_salon'         => $salon->id_salon,
                'id_promo'         => null,
                'kode_order'       => 'VYG-' . strtoupper(Str::random(8)),
                'date_order'       => $data['tanggal'],
                'total_pembayaran' => $service->harga,
                'total_diskon'     => 0,
                'status'           => 'pending',
            ]);

            $start = $data['waktu'];
            $end   = Carbon::createFromFormat('H:i', $start)
                ->addMinutes((int) ($service->durasi ?? 30))
                ->format('H:i');

            OrderDetail::create([
                'id_order'       => $order->id_order,
                'id_service'     => $service->id_service,
                'id_staff'       => $resolvedStaffId,
                'start_time'     => $start,
                'end_time'       => $end,
                'harga_at_order' => $service->harga,
                'subtotal'       => $service->harga,
                'catatan'        => $data['catatan'] ?? null,
                'status'         => 'pending',
            ]);

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

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Salon;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function create(string $slug)
    {
        $salon = Salon::active()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id_salon', $slug))
            ->with([
                'kota',
                'services' => fn ($q) => $q->where('status', 'active')->with('kategori'),
                'primaryImage',
            ])
            ->firstOrFail();

        return view('booking.create', compact('salon'));
    }

    public function store(Request $request, string $slug)
    {
        $request->validate([
            'id_service' => 'required|exists:service,id_service',
            'tanggal'    => 'required|date|after_or_equal:today',
            'waktu'      => 'required|string',
            'catatan'    => 'nullable|string|max:1000',
        ]);

        $salon = Salon::active()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id_salon', $slug))
            ->firstOrFail();

        $service = Service::where('status', 'active')->findOrFail($request->id_service);

        $order = DB::transaction(function () use ($request, $salon, $service) {
            $order = Order::create([
                'id_user'          => auth()->id(),
                'id_salon'         => $salon->id_salon,
                'id_promo'         => null,
                'kode_order'       => 'VYG-' . strtoupper(Str::random(8)),
                'date_order'       => $request->tanggal,
                'total_pembayaran' => $service->harga,
                'total_diskon'     => 0,
                'status'           => 'pending',
            ]);

            $start = $request->waktu;
            $end   = Carbon::createFromFormat('H:i', $start)
                ->addMinutes((int) ($service->durasi ?? 30))
                ->format('H:i');

            OrderDetail::create([
                'id_order'       => $order->id_order,
                'id_service'     => $service->id_service,
                'id_staff'       => null,
                'start_time'     => $start,
                'end_time'       => $end,
                'harga_at_order' => $service->harga,
                'subtotal'       => $service->harga,
                'catatan'        => $request->catatan,
                'status'         => 'pending',
            ]);

            return $order;
        });

        return redirect()
            ->route('booking.konfirmasi', $order->kode_order)
            ->with('success', 'Booking confirmed successfully.');
    }

    public function konfirmasi(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['salon.kota', 'details.service'])
            ->firstOrFail();

        return view('booking.konfirmasi', compact('order'));
    }

    public function batal(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $order->update(['status' => 'canceled']);

        return back()->with('success', 'Booking cancelled successfully.');
    }
}

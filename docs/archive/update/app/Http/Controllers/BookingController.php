<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // ── Tampilkan form booking ────────────────────────────────────────────
    public function create(string $slug)
    {
        $salon = Salon::active()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id_salon', $slug);
            })
            ->with(['kota', 'services' => fn ($q) => $q->active()->with('kategori'), 'primaryImage'])
            ->firstOrFail();

        return view('booking.create', compact('salon'));
    }

    // ── Simpan booking baru ───────────────────────────────────────────────
    public function store(Request $request, string $slug)
    {
        $request->validate([
            'id_service' => 'required|exists:service,id_service',
            'tanggal'    => 'required|date|after_or_equal:today',
            'waktu'      => 'required|string',
        ]);

        $salon   = Salon::active()
            ->where(fn ($q) => $q->where('slug', $slug)->orWhere('id_salon', $slug))
            ->firstOrFail();

        $service = Service::active()->findOrFail($request->id_service);

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

            OrderDetail::create([
                'id_order'   => $order->id_order,
                'id_service' => $service->id_service,
                'qty'        => 1,
                'harga'      => $service->harga,
                'catatan'    => $request->catatan,
            ]);

            return $order;
        });

        return redirect()->route('booking.konfirmasi', $order->kode_order);
    }

    // ── Halaman konfirmasi ────────────────────────────────────────────────
    public function konfirmasi(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->with(['salon.kota', 'details.service'])
            ->firstOrFail();

        return view('booking.konfirmasi', compact('order'));
    }

    // ── Batalkan booking ──────────────────────────────────────────────────
    public function batal(string $kode)
    {
        $order = Order::where('kode_order', $kode)
            ->where('id_user', auth()->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $order->update(['status' => 'canceled']);

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }
}

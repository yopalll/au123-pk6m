<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Show the review form for a completed order.
     */
    public function create(string $kode)
    {
        $order = $this->resolveReviewableOrder($kode);

        return view('akun.review.create', [
            'order' => $order->load(['salon', 'details.service']),
        ]);
    }

    /**
     * Persist a review and recompute salon aggregates.
     *
     * Aggregates are recomputed by ReviewObserver — but we still wrap the
     * Review::create in a transaction so we never half-write a row.
     */
    public function store(Request $request, string $kode)
    {
        $order = $this->resolveReviewableOrder($kode);

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'komentar' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($order, $data) {
            Review::create([
                'id_user'    => auth()->id(),
                'id_salon'   => $order->id_salon,
                'id_order'   => $order->id_order,
                'rating'     => (int) $data['rating'],
                'komentar'   => $data['komentar'],
                'is_visible' => true,
            ]);
        });

        return redirect()
            ->route('akun.bookings', ['tab' => 'selesai'])
            ->with('success', 'Thanks for sharing your experience.');
    }

    /**
     * Loads an order owned by the user that is eligible for a review:
     *  - status = success
     *  - has no existing review
     *
     * 404s otherwise so users can't probe other orders.
     */
    protected function resolveReviewableOrder(string $kode): Order
    {
        return Order::query()
            ->where('id_user', auth()->id())
            ->where('kode_order', $kode)
            ->where('status', 'success')
            ->whereDoesntHave('review')
            ->firstOrFail();
    }
}

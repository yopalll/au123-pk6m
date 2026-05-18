<?php

namespace App\Http\Controllers;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\Salon;
use Illuminate\Http\Request;

class AkunController extends Controller
{
    public function index()
    {
        $upcomingCount = Order::where('id_user', auth()->id())
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
            ->count();

        $favouriteCount = auth()->user()->favourites()->count();
        $promoCount     = auth()->user()->promos()->wherePivot('is_used', false)->count();

        return view('akun.index', compact('upcomingCount', 'favouriteCount', 'promoCount'));
    }

    public function bookings(Request $request)
    {
        $tab = $request->get('tab', 'mendatang');

        // Upcoming covers both unpaid (`pending`) and paid-but-unattended
        // (`confirmed`) bookings, since to the customer they're both
        // "I have an appointment coming up".
        $statusMap = [
            'mendatang'  => [OrderStatus::PENDING, OrderStatus::CONFIRMED],
            'selesai'    => [OrderStatus::SUCCESS],
            'dibatalkan' => [OrderStatus::CANCELED],
        ];

        // BUG-A09: Sanitise tab against known keys to prevent information leak
        // (an unknown tab value previously returned ALL orders for the user).
        $tab = in_array($request->get('tab', 'mendatang'), array_keys($statusMap), true)
            ? $request->get('tab', 'mendatang')
            : 'mendatang';

        $orders = Order::where('id_user', auth()->id())
            ->whereIn('status', $statusMap[$tab])
            ->with(['salon.kota', 'details.service', 'details.staff', 'review'])  // OPT-06: add details.staff pre-emptively
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('akun.bookings', compact('orders', 'tab'));
    }

    /**
     * Wishlist listing.
     */
    public function favorit()
    {
        $favourites = auth()->user()
            ->favourites()
            ->with(['kota', 'services.kategori', 'primaryImage'])
            ->orderByDesc('user_favourites.created_at')
            ->get();

        return view('akun.favorit', compact('favourites'));
    }

    /**
     * Toggle the heart icon on a salon card.
     */
    public function toggleFavorit(Salon $salon, Request $request)
    {
        $user = auth()->user();

        if ($user->favourites()->where('salon.id_salon', $salon->id_salon)->exists()) {
            $user->favourites()->detach($salon->id_salon);
            $favourited = false;
        } else {
            $user->favourites()->attach($salon->id_salon);
            $favourited = true;
        }

        if ($request->wantsJson()) {
            return response()->json(['favourited' => $favourited]);
        }

        return back()->with(
            'success',
            $favourited ? 'Saved to your favourites.' : 'Removed from your favourites.'
        );
    }

    public function pengaturan()
    {
        return view('akun.pengaturan');
    }

    public function updatePengaturan(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'nullable|string|max:100',
            'email'        => 'required|email|unique:users,email,' . auth()->id() . ',id_user',
            // BUG-A15: Include phone_number; used by Midtrans customer_details.phone.
            'phone_number' => 'nullable|string|max:30',
        ]);

        auth()->user()->update($request->only('first_name', 'last_name', 'email', 'phone_number'));

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Loyalty + claimed promos.
     */
    public function reward()
    {
        $promos = auth()->user()
            ->promos()
            ->orderByPivot('is_used', 'asc')
            ->orderBy('time_expired', 'asc')
            ->get();

        return view('akun.reward', compact('promos'));
    }
}

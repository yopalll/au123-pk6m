<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Salon;
use Illuminate\Http\Request;

class AkunController extends Controller
{
    public function index()
    {
        $upcomingCount = Order::where('id_user', auth()->id())
            ->whereIn('status', ['pending', 'confirmed'])
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
            'mendatang'  => ['pending', 'confirmed'],
            'selesai'    => ['success'],
            'dibatalkan' => ['canceled'],
        ];

        $orders = Order::where('id_user', auth()->id())
            ->when(isset($statusMap[$tab]), fn ($q) => $q->whereIn('status', $statusMap[$tab]))
            ->with(['salon.kota', 'details.service', 'review'])
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

        if ($user->favourites()->whereKey($salon->id_salon)->exists()) {
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
            'phone_number' => 'nullable|string|max:30|regex:/^[+\d\s\-()]+$/',
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

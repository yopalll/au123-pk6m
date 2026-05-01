<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AkunController extends Controller
{
    public function index()
    {
        $upcomingCount = Order::where('id_user', auth()->id())
            ->where('status', 'pending')
            ->count();

        return view('akun.index', compact('upcomingCount'));
    }

    public function bookings(Request $request)
    {
        $tab = $request->get('tab', 'mendatang');

        $statusMap = [
            'mendatang'  => 'pending',
            'selesai'    => 'success',
            'dibatalkan' => 'canceled',
        ];

        $orders = Order::where('id_user', auth()->id())
            ->when(isset($statusMap[$tab]), fn ($q) => $q->where('status', $statusMap[$tab]))
            ->with(['salon.kota', 'details.service'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('akun.bookings', compact('orders', 'tab'));
    }

    public function favorit()
    {
        $favourites = collect(); // pivot table user_favourites not yet implemented

        return view('akun.favorit', compact('favourites'));
    }

    public function pengaturan()
    {
        return view('akun.pengaturan');
    }

    public function updatePengaturan(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'required|email|unique:users,email,' . auth()->id() . ',id_user',
        ]);

        auth()->user()->update($request->only('first_name', 'last_name', 'email'));

        return back()->with('success', 'Profile updated successfully.');
    }

    public function reward()
    {
        return view('akun.reward');
    }
}

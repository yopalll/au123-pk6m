<?php

namespace App\Http\Controllers;

use App\Models\EmptyReturn;
use App\Models\EmptyReturnPhoto;
use App\Models\ProductOrderItem;
use App\Models\Salon;
use Illuminate\Http\Request;

class EmptyReturnController extends Controller
{
    public function index()
    {
        $totalBotol = (int) EmptyReturn::where('status', 'approved')->sum('jumlah');
        $estimasiKg = round($totalBotol * 0.05, 1); // asumsi 50 gram per botol

        return view('empty-return.index', compact('totalBotol', 'estimasiKg'));
    }

    public function create()
    {
        $purchasedProducts = ProductOrderItem::whereHas('order', fn ($q) => $q
            ->where('id_user', auth()->id())
            ->whereIn('status', ['delivered', 'completed']))
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id_product')
            ->values();

        $salons = Salon::where('status', 'active')->orderBy('nama_salon')->get(['id_salon', 'nama_salon', 'alamat']);

        return view('empty-return.create', compact('purchasedProducts', 'salons'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1|max:50',
            'metode' => 'required|in:dropoff,pickup',
            'id_salon' => 'required_if:metode,dropoff|nullable|exists:salon,id_salon',
            'alamat_pickup' => 'required_if:metode,pickup|nullable|string|max:500',
            'id_product' => 'nullable|exists:products,id_product',
            'foto' => 'nullable|array|max:3',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $return = EmptyReturn::create([
            'id_user' => auth()->id(),
            'id_product' => $request->id_product,
            'id_salon' => $request->metode === 'dropoff' ? $request->id_salon : null,
            'nama_produk' => $request->nama_produk,
            'jumlah' => $request->jumlah,
            'metode' => $request->metode,
            'alamat_pickup' => $request->alamat_pickup,
            'status' => 'pending',
        ]);

        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $foto) {
                EmptyReturnPhoto::create([
                    'id_return' => $return->id_return,
                    'photo_url' => $foto->store('empty-returns', 'public'),
                ]);
            }
        }

        return redirect()->route('emptyReturn.history')
            ->with('success', 'Pengajuan pengembalian terkirim! Kami verifikasi dalam 1-3 hari kerja.');
    }

    public function history()
    {
        $returns = EmptyReturn::where('id_user', auth()->id())
            ->with('photos')
            ->latest()
            ->paginate(10);

        return view('empty-return.history', compact('returns'));
    }
}

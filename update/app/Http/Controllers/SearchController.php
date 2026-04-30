<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q      = $request->input('q', '');
        $lokasi = $request->input('lokasi', '');

        $salons = Salon::active()
            ->with(['kota', 'services.kategori', 'primaryImage'])
            ->when($q, function ($query) use ($q) {
                $query->where('nama_salon', 'like', "%{$q}%")
                      ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', "%{$q}%"));
            })
            ->when($lokasi, function ($query) use ($lokasi) {
                $query->whereHas('kota', fn ($k) => $k->where('nama', 'like', "%{$lokasi}%"));
            })
            ->when(request('sort') === 'rating-tertinggi', fn ($q) => $q->orderByDesc('rating'))
            ->when(request('sort') === 'harga-terendah',   fn ($q) => $q->orderBy('services.harga'))
            ->when(! request('sort'),                      fn ($q) => $q->orderByDesc('total_review'))
            ->paginate(10)
            ->withQueryString();

        return view('cari.index', compact('salons'));
    }
}

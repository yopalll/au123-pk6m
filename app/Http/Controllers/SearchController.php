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
        $sort   = $request->input('sort');

        $salons = Salon::active()
            ->with(['kota', 'services.kategori', 'primaryImage'])
            ->withMin(['services as min_harga' => fn ($q) => $q->where('status', 'active')], 'harga')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nama_salon', 'like', "%{$q}%")
                      ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', "%{$q}%"));
                });
            })
            ->when($lokasi, function ($query) use ($lokasi) {
                // Real column on `kota` is `nama_kota`, not `nama`.
                $query->whereHas('kota', fn ($k) => $k->where('nama_kota', 'like', "%{$lokasi}%"));
            })
            ->when($sort === 'rating-tertinggi', fn ($qb) => $qb->orderByDesc('rating'))
            ->when($sort === 'harga-terendah',   fn ($qb) => $qb->orderBy('min_harga'))
            ->when(! $sort,                      fn ($qb) => $qb->orderByDesc('total_review'))
            ->paginate(10)
            ->withQueryString();

        return view('cari.index', compact('salons', 'q', 'lokasi'));
    }
}

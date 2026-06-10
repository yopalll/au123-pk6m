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

        return view('cari.index', compact('salons', 'q', 'lokasi', 'sort'));
    }

    /**
     * Autocomplete saran nama salon (JSON) untuk search bar navbar.
     */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $salons = Salon::active()
            ->where('nama_salon', 'like', "%{$q}%")
            ->with('kota')
            ->orderByDesc('total_review')
            ->limit(8)
            ->get();

        return response()->json(
            $salons->map(fn ($s) => [
                'nama'   => $s->nama_salon,
                'kota'   => $s->kota?->nama_kota,
                'rating' => $s->rating ? number_format($s->rating, 1) : null,
                'url'    => route('salon.show', $s->slug ?? $s->id_salon),
            ])
        );
    }
}

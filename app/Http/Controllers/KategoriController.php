<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Salon;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function show(string $slug, Request $request)
    {
        $kategori = Kategori::active()->where('slug', $slug)->firstOrFail();
        $sort = $request->input('sort');

        $salons = Salon::active()
            ->whereHas('services', fn ($q) => $q->where('id_kategori', $kategori->id_kategori)->where('status', 'active'))
            ->with([
                'kota',
                'services' => fn ($q) => $q->where('id_kategori', $kategori->id_kategori)->active()->with('kategori'),
                'primaryImage',
            ])
            ->withMin([
                'services as min_harga' => fn ($q) => $q->where('id_kategori', $kategori->id_kategori)->where('status', 'active'),
            ], 'harga')
            ->when($sort === 'rating-tertinggi', fn ($qb) => $qb->orderByDesc('rating'))
            ->when($sort === 'harga-terendah',   fn ($qb) => $qb->orderBy('min_harga'))
            ->when($sort === 'terbaru',          fn ($qb) => $qb->latest())
            ->when(! $sort,                      fn ($qb) => $qb->orderByDesc('total_review'))
            ->paginate(10)
            ->withQueryString();

        return view('kategori.show', compact('kategori', 'salons'));
    }
}

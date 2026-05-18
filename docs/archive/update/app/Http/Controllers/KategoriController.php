<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function show(string $slug)
    {
        $kategori = Kategori::active()->where('slug', $slug)->firstOrFail();

        $salons = Salon::active()
            ->whereHas('services', fn ($q) => $q->where('id_kategori', $kategori->id_kategori)->where('status', 'active'))
            ->with(['kota', 'services' => fn ($q) => $q->where('id_kategori', $kategori->id_kategori)->active(), 'primaryImage'])
            ->when(request('sort') === 'rating-tertinggi', fn ($q) => $q->orderByDesc('rating'))
            ->when(request('sort') === 'harga-terendah',  fn ($q) => $q->orderBy('services.harga'))
            ->when(request('sort') === 'terbaru',         fn ($q) => $q->latest())
            ->when(! request('sort'),                     fn ($q) => $q->orderByDesc('total_review'))
            ->paginate(10)
            ->withQueryString();

        return view('kategori.show', compact('kategori', 'salons'));
    }
}

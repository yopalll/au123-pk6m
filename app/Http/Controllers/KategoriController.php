<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Salon;
use App\Models\SubKategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    /**
     * /kategori/{slug} — halaman kategori utama (Hair, Hair Removal, ...).
     *
     * Juga handle "See all hair treatments" link dari navbar — yg arah-nya
     * ke /kategori/hair (dst). Listing semua salon yg punya minimal 1
     * service ber-id_kategori = ini.
     *
     * Special filter: ?filter=barbers di kategori 'mens' akan menampilkan
     * salon yg "Barbers-style" (nama salon / service mengandung "barber").
     * Filter ini tidak butuh row sub_kategori — query saja.
     */
    public function show(string $slug, Request $request)
    {
        $kategori = Kategori::active()
            ->with(['subKategori' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->firstOrFail();

        $sort         = $request->input('sort');
        $filter       = $request->input('filter');
        $isBarbersKey = $kategori->slug === 'mens' && $filter === 'barbers';

        $query = Salon::active()
            ->where(function ($q) use ($kategori) {
                $q->whereHas('kategoris', fn ($k) => $k->where(
                    'kategori.id_kategori',
                    $kategori->id_kategori
                ));
                $q->orWhereHas('services', fn ($s) => $s
                    ->where('id_kategori', $kategori->id_kategori)
                    ->where('status', 'active'));
            })
            ->when($isBarbersKey, function ($q) {
                // Special: "Barbers" — hanya salon yg jelas barber-style.
                $q->where(function ($w) {
                    $w->where('nama_salon', 'like', '%barber%')
                      ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', '%barber%'));
                });
            })
            ->with([
                'kota',
                'primaryImage',
                'services' => fn ($q) => $q
                    ->where('id_kategori', $kategori->id_kategori)
                    ->active()
                    ->with(['kategori', 'subKategori']),
            ])
            ->withMin([
                'services as min_harga' => fn ($q) => $q
                    ->where('id_kategori', $kategori->id_kategori)
                    ->where('status', 'active'),
            ], 'harga')
            ->when($sort === 'rating-tertinggi', fn ($qb) => $qb->orderByDesc('rating'))
            ->when($sort === 'harga-terendah',   fn ($qb) => $qb->orderBy('min_harga'))
            ->when($sort === 'terbaru',          fn ($qb) => $qb->latest())
            ->when(! $sort,                      fn ($qb) => $qb->orderByDesc('total_review'));

        $salons = $query->paginate(10)->withQueryString();

        return view('kategori.show', compact('kategori', 'salons', 'isBarbersKey'));
    }

    /**
     * /sub-kategori/{slug} — halaman sub_kategori (Pedicure, Blow Dry, dll).
     *
     * Listing salon yg punya minimal 1 service ber-id_sub_kategori = ini.
     */
    public function showSub(string $slug, Request $request)
    {
        $sub = SubKategori::active()
            ->with('kategori')
            ->where('slug', $slug)
            ->firstOrFail();

        $sort = $request->input('sort');

        $salons = Salon::active()
            ->whereHas('services', fn ($s) => $s
                ->where('id_sub_kategori', $sub->id_sub_kategori)
                ->where('status', 'active'))
            ->with([
                'kota',
                'primaryImage',
                'services' => fn ($q) => $q
                    ->where('id_sub_kategori', $sub->id_sub_kategori)
                    ->active()
                    ->with(['kategori', 'subKategori']),
            ])
            ->withMin([
                'services as min_harga' => fn ($q) => $q
                    ->where('id_sub_kategori', $sub->id_sub_kategori)
                    ->where('status', 'active'),
            ], 'harga')
            ->when($sort === 'rating-tertinggi', fn ($qb) => $qb->orderByDesc('rating'))
            ->when($sort === 'harga-terendah',   fn ($qb) => $qb->orderBy('min_harga'))
            ->when($sort === 'terbaru',          fn ($qb) => $qb->latest())
            ->when(! $sort,                      fn ($qb) => $qb->orderByDesc('total_review'))
            ->paginate(10)
            ->withQueryString();

        // Re-use template kategori.show: emulate $kategori dari sub_kategori utk header.
        $kategori = (object) [
            'name'      => $sub->name,
            'slug'      => $sub->slug,
            'deskripsi' => $sub->deskripsi,
        ];
        return view('kategori.show', compact('kategori', 'salons', 'sub'));
    }
}

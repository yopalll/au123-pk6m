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
     * Filter pakai pivot table `salon_kategori` LANGSUNG — bukan via
     * `services.id_kategori`. Pivot diisi oleh scraper saat visit URL
     * /treatment-group-{slug}/, jadi lebih akurat & cepat (pakai index)
     * dibanding scan tabel service.
     *
     * Special filter: ?filter=barbers di kategori 'mens' menampilkan
     * salon barber-style (nama salon mengandung "barber").
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
            // Pure pivot filter — tabel salon_kategori (M:N salon ↔ kategori)
            ->whereHas('kategoris', fn ($k) => $k
                ->where('kategori.id_kategori', $kategori->id_kategori))
            ->when($isBarbersKey, function ($q) {
                $q->where(function ($w) {
                    $w->where('nama_salon', 'like', '%barber%')
                      ->orWhereHas('services', fn ($s) => $s->where('nama', 'like', '%barber%'));
                });
            })
            ->with([
                'kota',
                'primaryImage',
                // Service preview di card — hanya yg match kategori ini
                'services' => fn ($q) => $q
                    ->where('id_kategori', $kategori->id_kategori)
                    ->active()
                    ->with(['kategori', 'subKategori']),
                // Chip pivot di card — semua sub_kategori salon ini
                'subKategoris' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('id_sub_kategori'),
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
     * Filter pakai pivot table `salon_sub_kategori` LANGSUNG. Diisi scraper
     * saat visit URL /treatment-{slug}/. Lebih akurat dibanding via service
     * (yang bisa miss kalau service tidak match keyword sub_kategori).
     */
    public function showSub(string $slug, Request $request)
    {
        $sub = SubKategori::active()
            ->with('kategori')
            ->where('slug', $slug)
            ->firstOrFail();

        $sort = $request->input('sort');

        $salons = Salon::active()
            // Pure pivot filter — tabel salon_sub_kategori (M:N salon ↔ sub_kategori)
            ->whereHas('subKategoris', fn ($s) => $s
                ->where('sub_kategori.id_sub_kategori', $sub->id_sub_kategori))
            ->with([
                'kota',
                'primaryImage',
                // Service preview hanya yg match sub_kategori ini
                'services' => fn ($q) => $q
                    ->where('id_sub_kategori', $sub->id_sub_kategori)
                    ->active()
                    ->with(['kategori', 'subKategori']),
                'subKategoris' => fn ($q) => $q
                    ->where('is_active', true)
                    ->orderBy('id_sub_kategori'),
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

        // SubKategori::kategori() is belongsToMany — take the first parent
        // so the view can access $kategori->name / ->deskripsi as a single model.
        $kategori = $sub->kategori->first();
        return view('kategori.show', compact('kategori', 'salons', 'sub'));
    }
}

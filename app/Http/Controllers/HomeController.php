<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Salon;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        // Query featured salons directly — caching Eloquent models with the
        // database cache driver causes serialization failures (models deserialize
        // as strings). This query is fast (top 8 by rating, indexed).
        $salons = Salon::active()
            ->with([
                'kota',
                'services.kategori',
                'primaryImage',
                'subKategoris' => fn ($q) => $q->where('is_active', true)->orderBy('id_sub_kategori')->limit(3),
            ])
            ->withCount('reviews')
            ->orderByDesc('rating')
            ->take(8)
            ->get();

        $categories = Cache::remember('home.categories', now()->addHours(24), fn () =>
            Kategori::active()->orderBy('name')->take(8)->get()->toArray()
        );
        // Hydrate back to objects for template compatibility
        $categories = collect($categories)->map(fn ($c) => (object) $c);

        return view('home', compact('salons', 'categories'));
    }
}

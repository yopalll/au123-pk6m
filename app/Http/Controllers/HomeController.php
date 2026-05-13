<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Salon;

class HomeController extends Controller
{
    public function index()
    {
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

        $categories = Kategori::active()
            ->orderBy('name')
            ->take(8)
            ->get();

        return view('home', compact('salons', 'categories'));
    }
}

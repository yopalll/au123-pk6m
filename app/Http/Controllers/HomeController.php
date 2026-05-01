<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\Salon;

class HomeController extends Controller
{
    public function index()
    {
        $salons = Salon::active()
            ->with(['kota', 'services.kategori', 'primaryImage'])
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

<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use App\Models\Kategori;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $salons = Salon::active()
            ->with(['kota', 'services.kategori', 'primaryImage', 'images'])
            ->withCount('reviews')
            ->orderByDesc('rating')
            ->take(8)
            ->get();

        return view('home', compact('salons'));
    }
}

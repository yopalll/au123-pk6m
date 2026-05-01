<?php

namespace App\Http\Controllers;

use App\Models\Salon;
use Illuminate\Http\Request;

class SalonController extends Controller
{
    public function show(string $slug)
    {
        // Support both slug and id_salon fallback
        $salon = Salon::active()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id_salon', $slug);
            })
            ->with([
                'kota',
                'images',
                'primaryImage',
                'services' => fn ($q) => $q->active()->with('kategori'),
                'reviews'  => fn ($q) => $q->with('user')->latest()->take(10),
                'staff',
            ])
            ->firstOrFail();

        return view('salon.show', compact('salon'));
    }
}

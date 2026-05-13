<?php

namespace App\Http\Controllers;

use App\Models\Salon;

class SalonController extends Controller
{
    public function show(string $slug)
    {
        $salon = Salon::active()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id_salon', $slug);
            })
            ->with([
                'kota',
                'images',
                'primaryImage',
                'services' => fn ($q) => $q->where('status', 'active')->with('kategori'),
                'reviews'  => fn ($q) => $q->where('is_visible', true)->with('user')->latest()->take(10),
                'staff',
                'kategoris'    => fn ($q) => $q->where('is_active', true)->orderBy('urutan'),
                'subKategoris' => fn ($q) => $q->where('is_active', true)->orderBy('id_sub_kategori'),
            ])
            ->firstOrFail();

        return view('salon.show', compact('salon'));
    }
}

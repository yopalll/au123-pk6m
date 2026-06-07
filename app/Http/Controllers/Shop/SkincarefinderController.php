<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UserSkincareProfile;
use Illuminate\Http\Request;

class SkincarefinderController extends Controller
{
    public function index()
    {
        return view('shop.skincare-finder');
    }

    public function result(Request $request)
    {
        $data = $request->validate([
            'skin_type' => 'required|in:oily,dry,combination,sensitive,normal',
            'skin_concern' => 'required|string',
            'looking_for' => 'nullable|string',
        ]);

        if (auth()->check()) {
            UserSkincareProfile::updateOrCreate(
                ['id_user' => auth()->id()],
                ['skin_type' => $data['skin_type'], 'skin_concerns' => $data['skin_concern']]
            );
        }

        $concern = explode(',', $data['skin_concern'])[0];

        $products = Product::where('status', 'active')
            ->where(fn ($q) => $q->where('skin_type', $data['skin_type'])->orWhere('skin_type', 'all'))
            ->where('skin_concern', 'like', "%{$concern}%")
            ->with('primaryImage')->limit(8)->get();

        // Fallback: kalau tidak ada yang cocok persis, tampilkan bestseller untuk skin_type itu
        if ($products->isEmpty()) {
            $products = Product::where('status', 'active')
                ->where(fn ($q) => $q->where('skin_type', $data['skin_type'])->orWhere('skin_type', 'all'))
                ->with('primaryImage')->orderByDesc('total_sold')->limit(8)->get();
        }

        return view('shop.skincare-finder-result', compact('products', 'data'));
    }
}

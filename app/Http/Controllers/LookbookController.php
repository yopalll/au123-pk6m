<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Lookbook;

class LookbookController extends Controller
{
    public function index()
    {
        $lookbooks = Lookbook::where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id_lookbook')
            ->get();

        $temas = $lookbooks->pluck('tema')->filter()->unique()->values();
        $featured = $lookbooks->first();

        return view('lookbook.index', compact('lookbooks', 'temas', 'featured'));
    }

    public function show(string $slug)
    {
        $lookbook = Lookbook::where('slug', $slug)
            ->where('is_published', true)
            ->with(['slides.items.product.primaryImage'])
            ->firstOrFail();

        $lookbook->increment('view_count');

        return view('lookbook.show', compact('lookbook'));
    }

    public function shopAll(string $slug)
    {
        $lookbook = Lookbook::where('slug', $slug)->with('slides.items.product')->firstOrFail();
        $user = auth()->user();
        $added = 0;

        foreach ($lookbook->slides as $slide) {
            foreach ($slide->items as $item) {
                $product = $item->product;
                if (! $product || $product->status !== 'active' || $product->stok <= 0) {
                    continue;
                }
                $cart = Cart::where('id_user', $user->id_user)->where('id_product', $item->id_product)->first();
                if ($cart) {
                    $cart->increment('qty');
                } else {
                    Cart::create(['id_user' => $user->id_user, 'id_product' => $item->id_product, 'qty' => 1]);
                }
                $added++;
            }
        }

        return back()->with('success', "{$added} produk dari lookbook ini ditambahkan ke keranjang.");
    }
}
